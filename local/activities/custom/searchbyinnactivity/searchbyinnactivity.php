<?php 

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Main\Loader;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\RequisiteTable;
use Bitrix\Main\Type\DateTime;

class CBPSearchByInnActivity extends BaseActivity
{
    private const ENTITY_TYPE_ID_COMPANY = 4;
    private const REQUISITE_PRESET_ID = 1;

    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'Inn' => '',
            'Text' => null,
        ];

        $this->SetPropertiesTypes([
            'Text' => ['Type' => FieldType::STRING],
        ]);
    }

    protected static function getFileName(): string
    {
        return __FILE__;
    }

    protected function internalExecute(): ErrorCollection 
    {
        $errors = parent::internalExecute(); 

        $token = "56512809ef9c58d8c5cd7e453bb1f11ec4044e5a";
        $secret = "2a47cf7f1f9fe36365b75ee39291e594ed27e973";
        
        $dadata = new Dadata($token, $secret);
        $dadata->init();

        $fields = array("query" => $this->Inn, "count" => 5);
        $response = $dadata->suggest("party", $fields);
        
        $companyName = 'Компания не найдена!';
        if (!empty($response['suggestions'])) { 
            $companyName = $response['suggestions'][0]['value']; 
            $companyINN = $response['suggestions'][0]['data']['inn'];  
            $companyOGRN = $response['suggestions'][0]['data']['ogrn'];  

            // Исправлено: $this-> вместо self::
            $cmpNew = $this->createCompanyFromDadata($response['suggestions'][0]);

            if ($cmpNew !=='-') { 
                $this->preparedProperties['Text'] = $companyName . ' -> ' . $companyINN . ' -> ' . $companyOGRN . ' -> ID новой компании = '. ' -> ' . $cmpNew;

                $rootActivity = $this->GetRootActivity();
                $rootActivity->SetVariable('IDcompany', $cmpNew);

            } else {
                $this->preparedProperties['Text'] = $companyName . ' -> ' . $companyINN . ' -> ' . $companyOGRN . ' -> ошибка создания'. ' -> ' . $cmpNew;
            } 
       
            $this->log($this->preparedProperties['Text']);
        }

        return $errors;
    } // <-- ДОБАВЛЕНА закрывающая скобка для internalExecute

    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        $map = [
            'Inn' => [
                'Name' => Loc::getMessage('SEARCHBYINN_ACTIVITY_FIELD_SUBJECT'),
                'FieldName' => 'inn',
                'Type' => FieldType::STRING,
                'Required' => true,
                'Options' => [],
            ],
        ];
        return $map;
    }

    // Исправлено: метод вынесен за пределы internalExecute
    private function createCompanyFromDadata(array $dadataData)
    {
        global $USER;
        
        if (!Loader::includeModule('crm')) {
            return null;
        }
        
        $userId = $USER->GetID() ?: 1;
        $now = new DateTime();

        $companyName = $dadataData['value'] ?? '';
        $data = $dadataData['data'] ?? [];

        $inn = $data['inn'] ?? '';
        $kpp = $data['kpp'] ?? '';
        $ogrn = $data['ogrn'] ?? '';
        $ogrnDate = $data['ogrn_date'] ?? '';
        
        $fullName = $data['name']['full_with_opf'] ?? $companyName;
        $shortName = $data['name']['short_with_opf'] ?? $companyName;

        $ogrnDateFormatted = '';
        if (!empty($ogrnDate) && is_numeric($ogrnDate)) {
            $timestamp = (int)($ogrnDate / 1000);
            $ogrnDateFormatted = date('Y-m-d', $timestamp);
        }

        try {
            $companyResult = CompanyTable::add([
                'DATE_CREATE' => $now,
                'DATE_MODIFY' => $now,
                'CREATED_BY_ID' => $userId,
                'MODIFY_BY_ID' => $userId,
                'ASSIGNED_BY_ID' => $userId,
                'TITLE' => $shortName ?: $companyName,
                'COMPANY_TYPE' => 'CUSTOMER',
                'OPENED' => 'N',
                'IS_MY_COMPANY' => 'N',
                'CATEGORY_ID' => 0,
                'LAST_ACTIVITY_TIME' => $now,
                'LAST_ACTIVITY_BY' => $userId
            ]);

            if (!$companyResult->isSuccess()) {
                return '-';
            }

            $companyId = $companyResult->getId();

            $requisiteResult = RequisiteTable::add([
                'ENTITY_TYPE_ID' => self::ENTITY_TYPE_ID_COMPANY,
                'ENTITY_ID' => $companyId,
                'PRESET_ID' => self::REQUISITE_PRESET_ID,
                'DATE_CREATE' => $now,
                'DATE_MODIFY' => $now,
                'CREATED_BY_ID' => $userId,
                'MODIFY_BY_ID' => $userId,
                'NAME' => 'Реквизиты ' . $inn,
                'ACTIVE' => 'Y',
                'SORT' => 500,
                'RQ_COMPANY_NAME' => $shortName,
                'RQ_COMPANY_FULL_NAME' => $fullName,
                'RQ_INN' => $inn,
                'RQ_KPP' => $kpp,
                'RQ_OGRN' => $ogrn,
                'RQ_COMPANY_REG_DATE' => $ogrnDateFormatted
            ]);

            // if (!$requisiteResult->isSuccess()) {
            //     return $companyId;
            // }

            return $companyId;

        } catch (\Exception $e) {
            return '-';
        }
    }
}