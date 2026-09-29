<?php declare(strict_types=1);

namespace Schedule\Http;

use Psr\Http\Message\ResponseInterface;

final class ResponseEmitter {

    public function emit(ResponseInterface $response): void {
        if (headers_sent()) {
            throw new \Exception('Headers already sent');
        }

        foreach($response->getHeaders() as $name => $values) {
            foreach($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        http_response_code($response->getStatusCode());
        echo $response->getBody();
    }

}
