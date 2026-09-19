<?php

namespace App\Item\Controllers;

use App\Coin\Domain\Exceptions\NotEnoughCoinsToReturn;
use App\Item\Application\BuyItemDTO;
use App\Item\Application\BuyItemUseCase;
use App\Item\Domain\Exceptions\ItemNotAvailable;
use App\Item\Domain\Exceptions\ItemNotValid;
use App\Item\Infrastructure\BuyItemRequest;
use App\MachineStatus\Domain\Exceptions\MachineStatusBalanceIsEmpty;
use App\MachineStatus\Domain\Exceptions\MachineStatusBalanceIsNotEnough;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

final readonly class BuyItemController
{
    public function __construct(private BuyItemUseCase $useCase)
    {
    }

    public function __invoke(
        #[MapQueryString(serializationContext: ['allow_extra_attributes' => false, 'collect_extra_attributes_errors' => true])] BuyItemRequest $request
    ): JsonResponse
    {
        try {
            $itemDelivered = $this->useCase->__invoke(new BuyItemDTO($request->name));
            $response = [
                'message' => 'Item delivered',
                'item' => $itemDelivered->item,
                'change' => $itemDelivered->coins,
            ];
            $httpCode = Response::HTTP_OK;
        } catch (ItemNotValid $itemNotValid) {
            $response = ['message' => $itemNotValid->getMessage()];
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (ItemNotAvailable $itemNotAvailable) {
            $response = ['message' => $itemNotAvailable->getMessage()];
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (MachineStatusBalanceIsEmpty $machineStatusBalanceIsEmpty) {
            $response = ['message' => $machineStatusBalanceIsEmpty->getMessage()];
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (MachineStatusBalanceIsNotEnough $machineStatusBalanceIsNotEnough) {
            $response = ['message' => $machineStatusBalanceIsNotEnough->getMessage()];
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (NotEnoughCoinsToReturn $notEnoughCoinsToReturn) {
            $response = ['message' => $notEnoughCoinsToReturn->getMessage()];
            $httpCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (\Exception $e) {
            $response = ['message' => 'Error buying item: ' . $e->getMessage()];
            $httpCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return new JsonResponse($response, $httpCode);
    }
}
