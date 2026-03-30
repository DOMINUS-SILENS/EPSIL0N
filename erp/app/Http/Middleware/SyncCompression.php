<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sync Compression Middleware
 * Handles gzip/brotli compression for sync payloads
 */
class SyncCompression
{
    /**
     * Minimum response size to compress (in bytes)
     */
    protected int $minCompressSize = 1024; // 1KB

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Decompress incoming request if compressed
        $this->decompressRequest($request);

        $response = $next($request);

        // Compress outgoing response if appropriate
        return $this->compressResponse($request, $response);
    }

    /**
     * Decompress incoming request body
     */
    protected function decompressRequest(Request $request): void
    {
        $encoding = $request->header('Content-Encoding');

        if (!$encoding || $request->method() === 'GET') {
            return;
        }

        $body = $request->getContent();

        if (empty($body)) {
            return;
        }

        $decompressed = match ($encoding) {
            'gzip' => @gzdecode($body),
            'deflate' => @gzinflate($body),
            'br' => extension_loaded('brotli') ? brotli_uncompress($body) : null,
            default => null,
        };

        if ($decompressed !== null && $decompressed !== false) {
            // Replace request content with decompressed data
            $request->initialize(
                $request->query->all(),
                $request->request->all(),
                $request->attributes->all(),
                $request->cookies->all(),
                $request->files->all(),
                $request->server->all(),
                $decompressed
            );
        }
    }

    /**
     * Compress outgoing response
     */
    protected function compressResponse(Request $request, Response $response): Response
    {
        // Only compress JSON responses
        if (!$this->shouldCompress($request, $response)) {
            return $response;
        }

        $content = $response->getContent();
        $encoding = $this->getPreferredEncoding($request);

        if (!$encoding || strlen($content) < $this->minCompressSize) {
            return $response;
        }

        $compressed = match ($encoding) {
            'br' => extension_loaded('brotli') ? brotli_compress($content) : null,
            'gzip' => gzencode($content, 6),
            'deflate' => gzdeflate($content, 6),
            default => null,
        };

        if ($compressed === null || $compressed === false) {
            return $response;
        }

        // Only use compression if it actually reduces size
        if (strlen($compressed) >= strlen($content)) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', $encoding);
        $response->headers->set('Content-Length', strlen($compressed));
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }

    /**
     * Check if response should be compressed
     */
    protected function shouldCompress(Request $request, Response $response): bool
    {
        // Skip if already encoded
        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        // Only compress JSON
        $contentType = $response->headers->get('Content-Type');
        if (!str_contains($contentType, 'application/json')) {
            return false;
        }

        // Don't compress if client doesn't accept it
        if (!$request->hasHeader('Accept-Encoding')) {
            return false;
        }

        return true;
    }

    /**
     * Get preferred encoding from Accept-Encoding header
     */
    protected function getPreferredEncoding(Request $request): ?string
    {
        $acceptEncoding = $request->header('Accept-Encoding', '');

        // Priority: brotli > gzip > deflate
        $encodings = ['br', 'gzip', 'deflate'];

        foreach ($encodings as $encoding) {
            if (str_contains($acceptEncoding, $encoding)) {
                return $encoding;
            }
        }

        return null;
    }
}
