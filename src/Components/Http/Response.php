<?php
namespace Kipchak\Core\Components\Http;

use Psr\Http\Message\ResponseInterface;

class Response
{
    /**
     * @param string|null $status the status to name; the HTTP name from Codes when null
     */
    public static function build(mixed $data, int $code, ?array $meta = null, ?string $status = null): array
    {
        $envelope =
            [
                'code' => $code,
                'status' => $status ?? Codes::getCode($code),
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
        return self::send($response, self::build($data, $code, $meta), $code, $cache, $cacheTTL, $cacheControlHeaders);
    }

    /**
     * An error in the envelope, with its status from ErrorStatus: {"code": 401, "status": "UNAUTHENTICATED", "data": $data}.
     * Middleware that refuses a request itself answers with this, so that its status matches what
     * kipchak/middleware-error writes for the same code.
     */
    public static function error(ResponseInterface $response, mixed $data, int $code): ResponseInterface
    {
        return self::send($response, self::build($data, $code, status: ErrorStatus::for($code)), $code);
    }

    private static function send(ResponseInterface $response, array $envelope, int $code, bool $cache = false, int $cacheTTL = 3600, array $cacheControlHeaders = []): ResponseInterface
    {
        $json = json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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