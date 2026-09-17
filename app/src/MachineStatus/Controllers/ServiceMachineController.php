<?php

namespace App\MachineStatus\Controllers;

use App\Coin\Domain\Exceptions\CoinNotValid;
use App\Coin\Domain\Exceptions\CoinWithNegativeQuantity;
use App\Coin\Domain\Exceptions\CoinWithNegativeValue;
use App\Item\Domain\Exceptions\ItemNotValid;
use App\Item\Domain\Exceptions\ItemWithNegativePrice;
use App\Item\Domain\Exceptions\ItemWithNegativeQuantity;
use App\MachineStatus\Application\MachineServiceUseCase;
use App\MachineStatus\Infrastructure\ServiceMachineRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final readonly class ServiceMachineController
{
    public function __construct(private MachineServiceUseCase $useCase)
    {
    }

    public function __invoke(
        #[MapRequestPayload(serializationContext: ['allow_extra_attributes' => false, 'collect_extra_attributes_errors' => true])] ServiceMachineRequest $request
    ): JsonResponse
    {
        try {
            $this->useCase->__invoke($request->coins, $request->items);
            $message = 'Machine serviced';
            $httpCode = Response::HTTP_OK;
        } catch (CoinNotValid $coinNotValid) {
            $message = $coinNotValid->getMessage();
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (CoinWithNegativeValue $coinWithNegativeValue) {
            $message = $coinWithNegativeValue->getMessage();
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (CoinWithNegativeQuantity $coinWithNegativeQuantity) {
            $message = $coinWithNegativeQuantity->getMessage();
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (ItemNotValid $itemNotValid) {
            $message = $itemNotValid->getMessage();
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (ItemWithNegativePrice $itemWithNegativePrice) {
            $message = $itemWithNegativePrice->getMessage();
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (ItemWithNegativeQuantity $itemWithNegativeQuantity) {
            $message = $itemWithNegativeQuantity->getMessage();
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (\Exception $e) {
            $message = 'Error servicing machine: ' . $e->getMessage();
            $httpCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return new JsonResponse(['message' => $message], $httpCode);
    }
}
