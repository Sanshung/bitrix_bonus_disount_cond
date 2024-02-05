<?php

namespace Local\Classes;

use Bitrix\Sale\Discount\Actions;
use \Bitrix\Main\Loader,
    \Bitrix\Highloadblock\HighloadBlockTable as HLB,
    \Bitrix\Highloadblock\HighloadBlockLangTable;

class BonusCond extends \CSaleActionCtrlBasketGroup
{
    public static function GetClassName()
    {
        return __CLASS__;
    }
    
    public static function GetControlID()
    {
        return "DiscountFromDirectory";
    }
    
    public static function GetControlDescr()
    {
        return parent::GetControlDescr();
    }
    
    public static function GetAtoms()
    {
        return static::GetAtomsEx(false, false);
    }
    
    public static function GetControlShow($arParams)
    {
        $arAtoms = static::GetAtomsEx(false, false);
        $arResult = [
            "controlId"   => static::GetControlID(),
            "group"       => false,
            "label"       => "Применить скидку из бонусов",
            "defaultText" => "",
            "showIn"      => static::GetShowIn($arParams["SHOW_IN_GROUPS"]),
            "control"     => [
                "Применить скидку из бонусов",
                $arAtoms["HLB"]
            ]
        ];
        
        return $arResult;
    }
    
    public static function GetAtomsEx($strControlID = false, $boolEx = false)
    {
        $boolEx = (true === $boolEx ? true : false);
        $arAtomList = [
            "HLB" => [
                "JS"   => [
                    "id"           => "HLB",
                    "name"         => "extra",
                    "type"         => "select",
                    "values"       => [1 => 'Да'],
                    "defaultText"  => "...",
                    "defaultValue" => "",
                    "first_option" => "..."
                ],
                "ATOM" => [
                    "ID"           => "HLB",
                    "FIELD_TYPE"   => "string",
                    "FIELD_LENGTH" => 255,
                    "MULTIPLE"     => "N",
                    "VALIDATE"     => "list"
                ]
            ],
        ];
        if ( ! $boolEx) {
            foreach ($arAtomList as &$arOneAtom) {
                $arOneAtom = $arOneAtom["JS"];
            }
            if (isset($arOneAtom)) {
                unset($arOneAtom);
            }
        }
        return $arAtomList;
    }
    
    public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
    {
        $mxResult = __CLASS__ . "::applyProductDiscount(" . $arParams["ORDER"] . ", " . "\"" . $arOneCondition["HLB"]
            . "\"" . ");";
        
        return $mxResult;
    }
    
    public static function applyProductDiscount(&$arOrder, $hlb)
    {
        $userId = $arOrder['USER_ID'];
        $bonus = Bonus::getUserBonus();
        
        if ($bonus > 0) {
            $discount = 0;
            $basketSum = 0;
            
            foreach ($arOrder['BASKET_ITEMS'] as $product) {
                $basketSum += $product['BASE_PRICE'];
            }
            
            if($basketSum < $bonus){
                $bonus = $basketSum;
            }
    
            $productCount = count((array)$arOrder['BASKET_ITEMS']);
            if ($productCount > 0) {
                $discount = intval($bonus / $productCount);
            }
            
            $discounts = [];
            //Применяем скидку
            foreach ($arOrder['BASKET_ITEMS'] as &$product) {
                $product['DISCOUNT_PRICE'] = $discount;
                $product['PRICE'] = $product['BASE_PRICE'] - $product['DISCOUNT_PRICE'];
            }
            unset($product);
            
            //Bonus::writeOffBonus($arOrder['USER_ID'], $bonus, $arOrder['ID']);
        }
    }
}
