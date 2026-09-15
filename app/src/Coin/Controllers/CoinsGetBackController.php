<?php

namespace App\Coin\Controllers;

use App\Coin\Application\CoinsGetBackUseCase;
use App\Coin\Domain\Exceptions\CoinNotValid;
use App\Coin\Domain\Exceptions\NotEnoughCoinsToReturn;
use App\MachineStatus\Domain\Exceptions\MachineStatusBalanceIsEmpty;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class CoinsGetBackController
{
    public function __construct(private CoinsGetBackUseCase $coinsGetBackUseCase)
    {
    }

    public function __invoke(): JsonResponse
    {
        try {
            $coinsReturned = $this->coinsGetBackUseCase->__invoke();
            $response = [
                'message' => 'Coins returned successfully',
                'coins' => $coinsReturned->coins
            ];
            $httpCode = Response::HTTP_OK;
        } catch (MachineStatusBalanceIsEmpty $machineStatusBalanceIsEmpty) {
            $response = ['message' => $machineStatusBalanceIsEmpty->getMessage()];
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (NotEnoughCoinsToReturn $notEnoughCoinsToReturn) {
            $response = ['message' => $notEnoughCoinsToReturn->getMessage()];
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (CoinNotValid $coinNotValid) {
            $response = ['message' => $coinNotValid->getMessage()];
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (\Exception $e) {
            $response = ['message' => 'Error returning coins: ' . $e->getMessage()];
            $httpCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return new JsonResponse($response, $httpCode);
    }
}
