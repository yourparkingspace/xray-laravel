<?php

declare(strict_types=1);

namespace Napp\Xray;

use Pkerrigan\Xray\Trace;
use GuzzleHttp\Middleware;
use Pkerrigan\Xray\Segment;
use GuzzleHttp\HandlerStack;
use Pkerrigan\Xray\HttpSegment;
use GuzzleHttp\Handler\CurlHandler;
use Psr\Http\Message\RequestInterface;
use Napp\Xray\Collectors\SegmentCollector;
use Symfony\Component\HttpFoundation\Request;

class Xray
{
    private $collector;

    public function __construct(SegmentCollector $collector)
    {
        $this->collector = $collector;
    }

    public function tracer(): Trace
    {
        return $this->collector->tracer();
    }

    public function current(): Segment
    {
        return $this->collector->current();
    }

    public function isEnabled(): bool
    {
        return $this->collector->isTracerEnabled();
    }

    public function addSegment(string $name, ?float $startTime = null, ?array $metadata = null): Segment
    {
        return $this->collector->addSegment($name, $startTime, $metadata);
    }

    public function addCustomSegment(Segment $segment, string $name): Segment
    {
        return $this->collector->addCustomSegment($segment, $name);
    }

    public function getSegment(string $name): ?Segment
    {
        return $this->collector->getSegment($name);
    }

    public function endSegment(string $name): void
    {
        $this->collector->endSegment($name);
    }

    public function hasAddedSegment(string $name): bool
    {
        return $this->collector->hasAddedSegment($name);
    }

    public function endCurrentSegment(): void
    {
        $this->collector->endCurrentSegment();
    }

    public function initHttpTracer(Request $request): void
    {
        $this->collector->initHttpTracer($request);
    }

    public function initCliTracer(string $name): void
    {
        $this->collector->initCliTracer($name);
    }

    public function submitHttpTracer($response): void
    {
        $this->collector->submitHttpTracer($response);
    }

    public function submitCliTracer(): void
    {
        $this->collector->submitCliTracer();
    }

    public function guzzleHandlerStack(): HandlerStack
    {
        $handler = new CurlHandler();
        $stack = HandlerStack::create($handler);

        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {

            $segmentName = $request->getMethod() . ': ' . $request->getUri()->getPath();

            $segment = (new HttpSegment())->setName($segmentName);
            $segment->setUrl('https://kiosk-heartbeat.services.pre-prod.yourparkingspace.co.uk/kiosks/EVK-OFFLINEPOST/status');
            $segment->setMethod($request->getMethod());
            $this->addCustomSegment($segment, $segmentName);

            return $request;
        }));

        $stack->push(function (callable $handler) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler) {
                $response = $handler($request, $options);

                $segmentName = $request->getMethod() . ': ' . $request->getUri()->getPath();

                $segment = $this->getSegment($segmentName);
                $segment->setResponseCode($response->wait()->getStatusCode());
                $segment->end();

                return $response;
            };
        });

        return $stack;
    }
}
