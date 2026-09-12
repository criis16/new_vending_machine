<?php

namespace App\Coin\Controllers;

use App\Coin\Application\CoinInsertUseCase;
use App\Coin\Application\CoinsInsertDTO;
use App\Coin\Domain\Exceptions\CoinNotValid;
use App\Coin\Domain\Exceptions\CoinWithNegativeValue;
use App\Coin\Infrastructure\CoinsInsertRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final readonly class CoinsInsertController
{
    public function __construct(private CoinInsertUseCase $useCase)
    {
    }

    public function __invoke(
        #[MapRequestPayload(serializationContext: ['allow_extra_attributes' => false, 'collect_extra_attributes_errors' => true])] CoinsInsertRequest $request
    ): JsonResponse
    {
        try {
            $this->useCase->__invoke(new CoinsInsertDTO($request->coin));
            $message = 'Coins Inserted';
            $httpCode = Response::HTTP_CREATED;
        } catch (CoinNotValid $coinNotValid) {
            $message = $coinNotValid->getMessage();
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (CoinWithNegativeValue $coinWithNegativeValue) {
            $message = $coinWithNegativeValue->getMessage();
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (\Exception $e) {
            $message = 'Error inserting coin: ' . $e->getMessage();
            $httpCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }
        return new JsonResponse(
            [
                'message' => $message
            ],
            $httpCode
        );
    }
}
