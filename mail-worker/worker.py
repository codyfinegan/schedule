"""
Local stand-in for the Cloudflare Worker in the inbound-RSVP flow.

Prod: Cloudflare Email Routing hands a reply straight to a Worker on receipt.
Here: there's no receive-triggered hook available locally, so this polls
Mailpit's API instead and does the same two jobs the Worker does:

  1. Parse the text/calendar; method=REPLY part out of the message and pull
     UID + PARTSTAT off the ATTENDEE line.
  2. POST it to the API's webhook with an HMAC-SHA256 signature, the same
     shared-secret scheme described in the architecture diagram.

Not meant to be a general-purpose iCal parser — just enough to exercise the
webhook end to end against real MIME.
"""

import email
import hashlib
import hmac
import json
import os
import re
import time
import urllib.error
import urllib.request
from email.policy import default as default_policy

MAILPIT_URL = os.environ.get("MAILPIT_URL", "http://mailpit:8025")
WEBHOOK_URL = os.environ.get("WEBHOOK_URL", "http://api-caddy/webhooks/rsvp")
RSVP_ADDRESS = os.environ.get("RSVP_ADDRESS", "rsvp@botc.example.test")
WEBHOOK_SECRET = os.environ.get("RSVP_WEBHOOK_SECRET", "dev-secret-change-me")
POLL_SECONDS = float(os.environ.get("POLL_SECONDS", "5"))

UID_RE = re.compile(r"^UID:(.+)$", re.MULTILINE)
ATTENDEE_RE = re.compile(r"^ATTENDEE[^:\n]*PARTSTAT=([A-Z-]+)[^:\n]*:mailto:(.+)$", re.MULTILINE)

seen_ids = set()


def get_json(url):
    with urllib.request.urlopen(url, timeout=10) as resp:
        return json.loads(resp.read())


def get_raw_message(message_id):
    url = f"{MAILPIT_URL}/api/v1/message/{message_id}/raw"
    with urllib.request.urlopen(url, timeout=10) as resp:
        return resp.read().decode("utf-8", errors="replace")


def extract_calendar_reply(raw):
    """Pull the text/calendar; method=REPLY body out of a raw RFC 822 message,
    decoding whatever Content-Transfer-Encoding it was sent with."""
    msg = email.message_from_string(raw, policy=default_policy)

    calendar_part = None
    for part in msg.walk():
        if part.get_content_type() == "text/calendar":
            calendar_part = part
            break
    if calendar_part is None:
        return None

    method = (calendar_part.get_param("method") or "").upper()
    body = calendar_part.get_content()
    if method != "REPLY" and "METHOD:REPLY" not in body.upper():
        return None

    uid_match = UID_RE.search(body)
    attendee_match = ATTENDEE_RE.search(body)
    if not uid_match or not attendee_match:
        return None

    return {
        "uid": uid_match.group(1).strip(),
        "partstat": attendee_match.group(1).strip(),
        "attendee": attendee_match.group(2).strip(),
    }


def sign(payload_bytes):
    return hmac.new(WEBHOOK_SECRET.encode(), payload_bytes, hashlib.sha256).hexdigest()


def forward(reply):
    body = json.dumps(reply).encode()
    signature = sign(body)
    req = urllib.request.Request(
        WEBHOOK_URL,
        data=body,
        method="POST",
        headers={
            "Content-Type": "application/json",
            "X-Signature": f"sha256={signature}",
        },
    )
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            print(f"forwarded uid={reply['uid']} partstat={reply['partstat']} -> {resp.status}")
    except urllib.error.HTTPError as e:
        print(f"webhook rejected uid={reply['uid']}: {e.code} {e.reason}")
    except urllib.error.URLError as e:
        print(f"webhook unreachable: {e.reason}")


def poll_once():
    try:
        data = get_json(f"{MAILPIT_URL}/api/v1/messages")
    except urllib.error.URLError as e:
        print(f"mailpit unreachable: {e.reason}")
        return

    for message in data.get("messages", []):
        message_id = message["ID"]
        if message_id in seen_ids:
            continue
        seen_ids.add(message_id)

        to_addresses = [addr.get("Address", "") for addr in message.get("To", [])]
        if RSVP_ADDRESS not in to_addresses:
            continue

        raw = get_raw_message(message_id)
        reply = extract_calendar_reply(raw)
        if reply is None:
            print(f"skipping {message_id}: no parseable text/calendar REPLY part")
            continue

        forward(reply)


def main():
    print(f"watching {MAILPIT_URL} for replies to {RSVP_ADDRESS}, forwarding to {WEBHOOK_URL}")
    while True:
        poll_once()
        time.sleep(POLL_SECONDS)


if __name__ == "__main__":
    main()
