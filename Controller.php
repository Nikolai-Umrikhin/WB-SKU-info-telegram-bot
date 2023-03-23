<?php

class MainController
{

    //Getting SKU info from Wildberries
    function getInfo($message)
    {

        $skuArray = explode(",", $message);
        $skuInfoArray = [];

        foreach ($skuArray as $i => $sku) {
            $count = 1;
            $sku = trim($sku);

            if (strlen($sku) == 8) {
                $volSku = substr($sku, 0, 3);
                $partSku = substr($sku, 0, 5);
            } else {
                $volSku = substr($sku, 0, 4);
                $partSku = substr($sku, 0, 6);
            }
            //URL with Base SKU info
            $dataUrl_1 = $this->curl_get('https://card.wb.ru/cards/detail?spp=19&pricemarginCoeff=1.0&reg=1&appType=1&emp=0&locale=ru&lang=ru&curr=rub&couponsGeo=2,12,3,18,15,21&dest=-1029256,-51490,12358263,123585548&nm=' . $sku);

            //URL with characteristic SKU info
            $dataUrl_2 = $this->curl_get('https://basket-0' . $count . '.wb.ru/vol' . $volSku . '/part' . $partSku . '/' . $sku . '/info/ru/card.json');
            while ($dataUrl_2['httpCode'] == 404 && $count < 10) {
                $count++;
                $dataUrl_2 = $this->curl_get('https://basket-0' . $count . '.wb.ru/vol' . $volSku . '/part' . $partSku . '/' . $sku . '/info/ru/card.json');
                sleep(rand(1, 2));
            }

            //URL with SKU Orders count
            $dataUrl_3 = $this->curl_get('https://product-order-qnt.wildberries.ru/by-nm/?nm=' . $sku);

            // DATA URL 4
            // https://search.wb.ru / exactmatch / ru / common / v4 / search?appType = 1 & couponsGeo = 12,7,3,6,18,22,21 & curr = rub & dest=-1075831, - 79374, - 367666, - 2133462 & emp = 0 & fdlvr = 29 & lang = ru & locale = ru & pricemarginCoeff = 1.0 & query=%D1 % 82 % D0 % B5 % D1 % 80 % D0 % BC % D0 % BE % D0 % BA % D1 % 80 % D1 % 83 % D0 % B6 % D0 % BA % D0 % B0 & reg = 0 & regions = 80,68,64,83,4,38,33,70,82,69,86,30,40,48,1,22,66,31 & resultset = filters & spp = 0 & suppressSpellcheck = false

            //Get JSON data from Telegram Request
            $html_1 = iconv('windows-1251', 'UTF-8', $dataUrl_1["html"]);
            $html_2 = iconv('windows-1251', 'UTF-8', $dataUrl_2['html']);
            $html_3 = iconv('windows-1251', 'UTF-8', $dataUrl_3['html']);

            $jsonArray1 = json_decode(substr(strstr($html_1, '{"id"'), 0, -3), true);
            $jsonArray2 = json_decode($html_2, true);
            $jsonArray3 = json_decode($html_3, true);

            if ($jsonArray1) {

                $prodID = $jsonArray1['id'];
                $basicPriceU = substr($jsonArray1['extended']['basicPriceU'], 0, -2);
                $basicSale = $jsonArray1['extended']['basicSale'];
                $priceU = substr($jsonArray1['priceU'], 0, -2);
                $prodName = $jsonArray1['name'];
                $prodBrand = $jsonArray1['brand'];

                $prodDescription = $jsonArray2['description'];

                $ordersCount = $jsonArray3[0]['qnt'];

                $skuInfoArray[$i] = $prodName . ";" . $prodID . ";" . $priceU . ";" . $basicSale . ";" . $basicPriceU . ";" . $prodBrand . ";" . $ordersCount . ";" . $prodDescription;

            } else {
                $skuInfoArray[$i] = 'Артикул не найден';
            }

        }

        return $skuInfoArray;
    }


    function curl_get($url, $referer = 'www.google.com')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT,
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.92 Safari/537.36");
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $html = iconv('UTF-8', 'windows-1251', curl_exec($ch));
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result =
            [
                'html' => $html,
                'httpCode' => $httpCode
            ];

        curl_close($ch);
        return $this->result = $result;
    }

    //Create CSV file with goods info and return path to file.
    function createCSV($prodInfo, $data)
    {
        $cloumnNames = "Наименование;Артикул;Цена;Скидка;Цена со скидкой, руб.;Бренд;Куплено, шт.;Описание\n";
        $path = "../WB_telegram_bot/reports/ID-" . $data['chat']['id'] . "-report-" . date("Ymd.h-i-s",
                strtotime("now")) . '.csv';
        $fp = fopen($path, 'a');
        file_put_contents($path, b"\xEF\xBB\xBF" . $cloumnNames, FILE_APPEND);
        foreach ($prodInfo as $value) {
            file_put_contents($path, $value . "\n", FILE_APPEND);
        }
        fclose($fp);
        return $path;
    }


    //Sending CSV file to the user
    function sendDocument($chatID, $file)
    {


        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://api.telegram.org/bot' . TOKEN . '/sendDocument?chat_id=' . $chatID,
        ]);
        $cFile = new CURLFile($file);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            "document" => $cFile
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    //Sending message to the user
    function sendTelegram($method, $data)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://api.telegram.org/bot' . TOKEN . '/' . $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"))
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return (json_decode($result, 1) ? json_decode($result, 1) : $result);
    }

}


?>
