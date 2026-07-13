<?php
namespace Kipchak\Core\Components\Http;

use Psr\Http\Message\ResponseInterface;

class Response
{
    public static function build(mixed $data, int $code, ?array $meta = null): array
    {
        $envelope =
            [
                'code' => $code,
                'status' => Codes::getCode($code),
            ];

        // Endpoint metadata (pagination, counts) sits between status and data.
        if ($meta !== null) {
            $envelope['meta'] = $meta;
        }

        $envelope['data'] = $data;

        return $envelope;
    }

    public static function json(ResponseInterface $response, mixed $data, int $code, bool $cache = false, int $cacheTTL = 3600, array $cacheControlHeaders = [], ?array $meta = null): ResponseInterface
    {
        $json = json_encode(self::build($data, $code, $meta), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($json);

        if (empty($cacheControlHeaders)) {
            $headersString = '';
        } else {
            $headersString = implode(',', $cacheControlHeaders) . ',';
        }
        if ($cache) {
            return $response->withHeader('Content-Type', 'application/json')
                ->withAddedHeader('Cache-Control', $headersString . 'max-age=' . $cacheTTL)
                ->withAddedHeader('ETag', md5($json))
                ->withAddedHeader('X-Powered-By', 'Kipchak by Mamluk')
                ->withStatus($code);
        }

        return $response->withHeader('Content-Type', 'application/json')
            ->withAddedHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->withAddedHeader('X-Powered-By', 'Kipchak by Mamluk')
            ->withStatus($code);
    }

    public static function redirect(ResponseInterface $response, string $url, int $code = 302): ResponseInterface
    {
        return $response->withStatus($code)
            ->withHeader('Location', $url)
            ->withAddedHeader('X-Powered-By', 'Kipchak by Mamluk');
    }

    public static function raw(ResponseInterface $response, mixed $data, int $code, bool $cache = false, int $cacheTTL = 3600, array $cacheControlHeaders = []): ResponseInterface
    {
        $response->getBody()->write($data);

        if (empty($cacheControlHeaders)) {
            $headersString = '';
        } else {
            $headersString = implode(',', $cacheControlHeaders) . ',';
        }
        if ($cache) {
            return $response->withHeader('Content-Type', 'application/json')
                ->withAddedHeader('Cache-Control', $headersString . 'max-age=' . $cacheTTL)
                ->withAddedHeader('ETag', md5($data))
                ->withAddedHeader('X-Powered-By', 'Kipchak by Mamluk')
                ->withStatus($code);
        }

        return $response->withHeader('Content-Type', 'text/*')
            ->withAddedHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->withAddedHeader('X-Powered-By', 'Kipchak by Mamluk')
            ->withStatus($code);
    }
}