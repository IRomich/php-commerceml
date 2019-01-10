<?php namespace Zenwalker\CommerceML\Model;

use Zenwalker\CommerceML\ORM\Model;

class Product extends Model
{
    /**
     * @var string $id
     */
    public $id;

    /**
     * @var string $name
     */
    public $name;

    /**
     * @var string $sku
     */
    public $sku;

    /**
     * @var string $unit
     */
    public $unit;

    /**
     * @var string $description
     */
    public $description;

    /**
     * @var int $quantity
     */
    public $quantity;

    /**
     * @var array $price
     */
    public $price = [];

    /**
     * @var array $categories
     */
    public $categories = [];

    /**
     * @var array $requisites
     */
    public $requisites = [];

    /**
     * @var array $properties
     */
    public $properties = [];

    /**
     * @var string $status 
     */
    public $status;

    /**
     * @var string $image
     */
    public $image;

    /**
     * @var array $offers 
     */
    public $offers;

    /**
     * @var int $basicUnitCode 
     */
    public $basicUnitCode;
    
    /**
     * Class constructor.
     *
     * @param string [$importXml]
     * @param string [$offersXml]
     */
    public function __construct($importXml = null, $offersXml = null)
    {
        $this->name        = '';
        $this->quantity    = 0;
        $this->description = '';
        if (!is_null($importXml)) {
            $this->loadImport($importXml);
        }
        $this->offers = [];
        if (!is_null($offersXml)) {
            foreach ($offersXml as $offer) {
                $this->loadOffers($offer);
            }
        }
    }

    /**
     * Load primary data from import.xml.
     *
     * @param \SimpleXMLElement $xml
     *
     * @return void
     */
    public function loadImport($xml)
    {
        $this->id = trim($xml->Ид);

        $this->name        = trim($xml->Наименование);
        $this->description = trim($xml->Описание);

        $this->sku  = trim($xml->Артикул);
        $this->unit = trim($xml->БазоваяЕдиница);
        $this->status = $xml->attributes()["Статус"];

        if (property_exists($xml->БазоваяЕдиница->attributes(), "Код")){
            $code = $xml->БазоваяЕдиница->attributes()->Код;
            $this->basicUnitCode = trim($code[0]);
        } else{
            $this->basicUnitCode = 0;
        }

        if (isset($xml->Картинка)){
            $this->image = [];
            foreach ($xml->Картинка as $image) {
                array_push($this->image, trim($xml->Картинка));
            }
        } else{
            if (array_key_exists("ОписаниеФайла", $xml->ЗначенияРеквизитов->ЗначениеРеквизита)){
                $this->image = explode("#", $xml->ЗначенияРеквизитов->ЗначениеРеквизита["ОписаниеФайла"])[0];
            } else{
                $this->image = NULL;
            }
        }

        if ($xml->Группы) {
            foreach ($xml->Группы->Ид as $categoryId) {
                $this->categories[] = (string)$categoryId;
            }
        }

        if ($xml->ЗначенияРеквизитов) {
            foreach ($xml->ЗначенияРеквизитов->ЗначениеРеквизита as $value) {
                $name                    = (string)$value->Наименование;
                $this->requisites[$name] = (string)$value->Значение;
            }
        }

        if ($xml->ЗначенияСвойств) {
            foreach ($xml->ЗначенияСвойств->ЗначенияСвойства as $prop) {

                $id    = (string)$prop->Ид;
                $value = (string)$prop->Значение;

                if ($value) {
                    $this->properties[$id] = $value;
                }
            }
        }
    }

    /**
     * Load primary data form offers.xml.
     *
     * @param \SimpleXMLElement $xml
     *
     * @return void
     */
    public function loadOffers($xml)
    {
        $offer = [];
        if ($xml->Количество) {
            $offer["quantity"] = (int)$xml->Количество;
        }
        $offer["name"] = (string)$xml->Наименование;
        $offer["id"] = "";
        //$offer["status"] = $xml->attributes()["Статус"];
        if (strpos($xml->Ид, "#") !== false){
            $offer["id"] = (string)explode("#", $xml->Ид)[1];
        } else{
            $offer["id"] = (string)$xml->Ид;
        }

        /*  Получение характеристик товара  */
        $offer["characteristics"] = [];
        if ($xml->ХарактеристикиТовара){
            foreach ($xml->ХарактеристикиТовара->ХарактеристикаТовара as $characteristic) {
                $id = (string)$characteristic->Ид;
                $offer["characteristics"][$id] = [
                    "type"  => $id,
                    "name"  => (string)$characteristic->Наименование,
                    "value" => (string)$characteristic->Значение
                ];
            }
        }

        if ($xml->Цены) {
            foreach ($xml->Цены->Цена as $price) {
                $id = (string)$price->ИдТипаЦены;
                $offer["price"][$id] = [
                    'type'     => $id,
                    'currency' => (string)$price->Валюта,
                    'value'    => (float)$price->ЦенаЗаЕдиницу
                ];
            }
        }
        array_push($this->offers, $offer);
    }

    /**
     * Get price by type.
     *
     * @param string $type
     *
     * @return float
     */
    public function getPrice($type)
    {
        foreach ($this->price as $price) {
            if ($price['type'] == $type) {
                return $price['value'];
            }
        }

        return 0;
    }
}
