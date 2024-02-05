# bitrix_bonus_disount_cond
Bitrix бонусная система реализованная через личный счет и правила работы с корзиной


Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    '\Local\Classes\Bonus' => '/local/classes/Bonus.php',
    '\Local\Classes\BonusCond' => '/local/classes/BonusCond.php',
]);
\Bitrix\Main\EventManager::getInstance()->addEventHandler('sale', 'OnCondSaleActionsControlBuildList', array('\Local\Classes\BonusCond', 'GetControlDescr'));


use Bitrix\Main\EventManager;

$eventManager = EventManager::getInstance();
$eventManager->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    ['\Local\Classes\Bonus', 'OnSaleOrderSaved']
);
