<?php

namespace App\Controller;

use App\Entity\Products;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductsController extends AbstractController
{
    /**
     * @Route("/products", name="products")
     */
    public function index()
    {
        $request = new Request($_GET);

        /** @var Products $product */
        $productId = $request->query->get('id');
        $product = $this->getDoctrine()->getRepository('App:Products')->find($productId);

        if ($productId === null || $product === null) {
            return new JsonResponse(['status' => 'Failed', 'message' => 'Invalid product id']);
        }

        $currency = $request->query->get('currency');
        $exchangeRate = $this->getExchangeRate($currency);

        $data = [
            'product id' => $product->getId(),
            'product name' => $product->getName(),
            'price in PLN' => $product->getPrice(),
            'price in ' . $currency => $this->calculatePrice($product->getPrice(), $exchangeRate)
        ];

        if ($currency === null || $exchangeRate === null) {
            unset($data['price in ' . $currency]);
            return new JsonResponse(['status' => 'Warning', 'message' => 'Missing currency', 'data' => $data]);
        }

        return new JsonResponse(['status' => 'success', 'data' => $data]);
    }

    public function calculatePrice($price, $exchangeRate)
    {
        if ($exchangeRate === null) {
            return null;
        }

        return $price / $exchangeRate;
    }

    public function getExchangeRate(string $currency)
    {
        $link = 'http://api.nbp.pl/api/exchangerates/rates/A/' . $currency;

        $headers = get_headers($link);
        $status = substr($headers[0], 9, 3);

        if ($status !== '200') {
            return null;
        }

        $data = json_decode(file_get_contents($link), true);
        $currencyValue = $data['rates'][0]['mid'];

        return $currencyValue;
    }
}
