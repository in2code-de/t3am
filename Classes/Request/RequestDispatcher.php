<?php

declare(strict_types=1);

namespace In2code\T3AM\Request;

use Exception;
use In2code\T3AM\Server\ServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;
use ReflectionMethod;
use Throwable;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function call_user_func_array;
use function settype;
use function sprintf;

class RequestDispatcher implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        [$class, $method] = $request->getAttribute('route');

        $response = new JsonResponse();

        try {
            $queryParams = $request->getQueryParams();
            $arguments = $this->mapQueryParamsToArguments($queryParams, $class, $method);
            try {
                $object = GeneralUtility::makeInstance($class);
                $data = call_user_func_array([$object, $method], $arguments);
                $payload = ['code' => 1496395280, 'error' => false, 'message' => 'ok', 'data' => $data];
            } catch (Throwable $throwable) {
                $payload = [
                    'code' => 1496395387,
                    'error' => true,
                    'message' => sprintf('Exception: [%d] %s', $throwable->getCode(), $throwable->getMessage()),
                    'data' => [],
                ];
            }
        } catch (Exception $exception) {
            $payload = [
                'code' => $exception->getCode(),
                'error' => true,
                'message' => $exception->getMessage(),
                'data' => [],
            ];
        }

        $response->setPayload($payload);

        return $response;
    }

    /**
     * @throws ServerException
     */
    protected function mapQueryParamsToArguments(array $queryParams, string $class, string $action): array
    {
        $arguments = [];

        try {
            $reflectionMethod = new ReflectionMethod($class, $action);
        } catch (ReflectionException $exception) {
            throw new ServerException('Can not examine route target', 1520607184, $exception);
        }
        $reflectionParameters = $reflectionMethod->getParameters();

        foreach ($reflectionParameters as $position => $reflectionParameter) {
            $name = $reflectionParameter->getName();
            $value = $queryParams[$name] ?? null;

            if (null === $value && !$reflectionParameter->allowsNull()) {
                throw new ServerException(sprintf('Missing parameter $%s', $name), 1496395204);
            } else {
                $type = $reflectionParameter->getType();
                if (null !== $type) {
                    $typeName = $type->getName();
                    settype($value, $typeName);
                }
                $arguments[$position] = $value;
            }
        }
        return $arguments;
    }
}
