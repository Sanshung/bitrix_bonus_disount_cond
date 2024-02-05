<?php

namespace Local\Classes;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Grid\Declension;
use Bitrix\Main\Loader;
use \Bitrix\Main\Event;
use Bitrix\Sale;

class Bonus
{
    const PERCENT = 5;
    const SECTION_DISABLE = [720];
    
    public static function getBonus($productID, $sectionID = 0, $price)
    {
        if (empty($sectionID)) {
            $sectionID = self::getSection($productID);
        }
        
        if ( ! empty(array_intersect(self::SECTION_DISABLE, (array)$sectionID))) {
            return 0;
        }
        
        $price = $price / 100 * self::PERCENT;
        return intval($price);
    }
    
    public static function getDeclension($price)
    {
        if ($price > 0) {
            $declension = new Declension('бонус', 'бонуса', 'бонусов');
            return $declension->get($price);
        }
    }
    
    public static function OnSaleOrderSaved(Event $event)
    {
        $bonusSum = 0;
        $bonusProp = 0;
        $basketSum = 0;
        global $USER;
        
        $order = $event->getParameter("ENTITY");
        $orderId = $order->getId();
        
        if ($event->getParameter("IS_NEW") && $USER->GetID() > 0) {
            $bonus = self::getUserBonus();
            if ($bonus > 0) {
                $basket = $order->getBasket();
                foreach ($basket as $basketItem) {
                    $basketSum += $basketItem->getFinalPrice();
                }
                if ($basketSum < $bonus) {
                    $bonus = $basketSum;
                }
                
                self::writeOffBonus($USER->GetID(), $bonus, $orderId);
            }
            return;
        }
        
        
        $statusId = $order->getField('STATUS_ID');
        $userId = $order->getUserId();
        
        if ($statusId != 'F') {
            return;
        }
        
        $propertyCollection = $order->getPropertyCollection();
        
        foreach ($propertyCollection as $propertyValue) {
            if ($propertyValue->getField('CODE') == 'BONUS') {
                $bonusProp = $propertyValue->getField('VALUE');
                break;
            }
        }
        
        if ($bonusProp > 0) {
            return;
        }
        
        $basket = $order->getBasket();
        foreach ($basket as $basketItem) {
            $price = $basketItem->getFinalPrice();
            $productID = $basketItem->getProductId();
            $productSection = self::getSection($productID);
            $bonusSum += self::getBonus($productID, $productSection, $price);
        }
        
        if ($bonusSum > 0) {
            $propertyCollection = $order->getPropertyCollection();
            foreach ($propertyCollection as $propertyValue) {
                if ($propertyValue->getField('CODE') == 'BONUS') {
                    $propertyValue->setValue($bonusSum);
                }
            }
            $order->save();
            
            self::addBonus($userId, $bonusSum, $orderId);
        }
    }
    
    public static function getSection($id)
    {
        Loader::includeModule('iblock');
        
        $productSection = [];
        $rsSections = \CIBlockElement::GetElementGroups($id, true);
        while ($arSection = $rsSections->Fetch()) {
            $productSection[$arSection['ID']] = $arSection['ID'];
        }
        return $productSection;
    }
    
    public static function addBonus($userId, $price, $orderID = 0)
    {
        if ($price > 0 && $userId > 0) {
            Loader::includeModule('sale');
            
            \CSaleUserAccount::UpdateAccount($userId, $price, "RUB", "MANUAL", $orderID, "Бонусы за заказ " . $orderID);
        }
    }
    
    public static function writeOffBonus($userId, $price, $orderID = 0)
    {
        if ($price > 0 && $userId > 0) {
            Loader::includeModule('sale');
            
            \CSaleUserAccount::UpdateAccount($userId, -$price, "RUB", "MANUAL", $orderID,
                "Списание бонусы за заказ " . $orderID);
        }
    }
    
    public static function getUserBonus()
    {
        global $USER;
        $arResult = \CSaleUserAccount::GetByUserID($USER->GetID(), 'RUB');
        if ( ! empty($arResult['CURRENT_BUDGET'])) {
            return intval($arResult['CURRENT_BUDGET']);
        } else {
            return 0;
        }
    }
    
    public static function getBonusBasket()
    {
        $bonusSum = 0;
        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
        foreach ($basket as $basketItem) {
            $price = $basketItem->getFinalPrice();
            $productID = $basketItem->getProductId();
            $productSection = self::getSection($productID);
            $bonusSum += self::getBonus($productID, $productSection, $price);
        }
        return intval($bonusSum);
    }
}
