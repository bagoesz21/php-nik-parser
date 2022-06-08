<?php

namespace Bagoesz21\PhpNikParser;

use Carbon\Carbon;

class NIKParser
{
    /** @var string */
    protected $nik;

    /**
     * 0 = male
     * + 40 = female
     * @var int
     */
    protected $valueGender = 40;

    protected $regions = [];

    /**
     * @param boolean $autoloadRegion
     */
    public function __construct($autoloadRegion = false)
    {
        $this->autoloadRegion($autoloadRegion);
    }

    /**
     * Static
     *
     * @param boolean $autoloadRegion
     * @return static
     */
    public static function make($autoloadRegion = false){
        $class = get_called_class();
        return (new $class($autoloadRegion));
    }

    /**
     * @param string $val
     * @return self
     */
    public function setNIK($val)
    {
        if(empty($val))return $this;
        $this->nik = $this->clean($val);
        return $this;
    }

    public function clean($val)
    {
        if(empty($val))return '';
        $val = trim($val);
        $val = str_replace(' ', '', $val);
        $val = str_replace('.', '', $val);
        return $val;
    }

    public function length()
    {
        return strlen($this->nik);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return ($this->length() === 16);
    }

    /**
     * @return array
     */
    public function listGender()
    {
        return [
            0 => [
                'key' => 0,
                'text' => 'Perempuan'
            ],
            1 => [
                'key' => 1,
                'text' => 'Laki-Laki'
            ],
        ];
    }

    /**
     * @param int $key
     * @return null|string
     */
    public function getGenderByKey($key){
        $gender = array_filter($this->listGender(), function($gender) use($key){
            return $gender['key'] === (int)$key;
        });
        if(empty($gender))return null;
        return $gender['text'];
    }

    /**
     * Get an item from an array
     *
     * @param  array  $array
     * @param  string|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function array_get($array, $key, $default = null)
    {
        if (! is_array($array)) {
            return $default;
        }

        if (is_null($key)) {
            return $array;
        }

        if(array_key_exists($key, $array)){
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    public function parseBirthDateWithGender()
    {
        return substr($this->nik, 6, 6);
    }

    public function parseBirthDate()
    {
        return $this->parseBirthDay() . '' . substr($this->nik, 8, 4);
    }

    public function parseBirthDayWithGender()
    {
        return substr($this->nik, 6, 2);
    }

    /**
     * @return string
     */
    public function parseBirthDay()
    {
        $val = (int)$this->parseBirthDayWithGender();

        if($this->isFemale($val)){
            $val = $val - $this->valueGender;
        }
        $val = str_pad($val, 2, '0', STR_PAD_LEFT);
        return $val;
    }

    public function parseGender()
    {
        $val = $this->parseBirthDayWithGender();
        return ($this->isFemale($val)) ? $this->getGenderByKey(0) : $this->getGenderByKey(1);
    }

    /**
     * @param int $val
     * @return bool
     */
    public function isFemale($val)
    {
        return (int)$val >= $this->valueGender;
    }

    /**
     * @param int $val
     * @return bool
     */
    public function isMale($val)
    {
        return !$this->isFemale($val);
    }

    public function parseBirthMonth()
    {
        $val = $this->parseBirthDate();
        return substr($val, 2, 2);
    }

    public function parseBirthYear()
    {
        $val = $this->parseBirthDate();
        return substr($val, 4, 2);
    }

    public function parseFullYear()
    {
        $val =  $this->parseBirthYear();
        $century = $this->parseCenturyBirth($val);
        $fullYear = $century . $val;
        return $fullYear;
    }

    public function parseCenturyBirth($val)
    {
        if(($val + 2000) > (int)date('Y')){
            $century = '19';
        }else{
            $century = '20';
        }
        return $century;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function parseBirthToCarbon()
    {
        $val = $this->parseBirthDay() . $this->parseBirthMonth() . $this->parseFullYear();
        return Carbon::createFromFormat('dmY', $val);
    }

    public function parseRandomNumber()
    {
        return substr($this->nik, $this->length() - 4, 4);
    }

    /**
     * Formatted NIK. Length 16 Character
     * 2 char province code, 2 char city code, 2 char district, 4 char date birth, 4 char random
     *
     * @return string
     */
    public function formattedNik(){
        if(empty($this->nik))return '';

        $arr = [
            $this->parseProvinceRegion(),
            $this->parseCityRegion(),
            $this->parseDistrictRegion(),
            $this->parseBirthDateWithGender(),
            $this->parseRandomNumber(),
        ];
        return implode('.', $arr);
    }

    public function formattedNumber()
    {
        return $this->clean($this->nik);
    }

    public function loadRegionData()
    {
        $path = __DIR__ . '../regions.json';

        $this->regions = json_decode(file_get_contents($path), true);
        return $this;
    }

    /**
     * @param boolean $toggle
     * @return self
     */
    public function autoloadRegion($toggle = true)
    {
        if($toggle){
            $this->loadRegionData();
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isLoadRegion()
    {
        return !empty($this->regions);
    }

    public function loadMissingRegion()
    {
        if(!$this->isLoadRegion()){
            $this->loadRegionData();
        }
        return $this;
    }

    public function parseRegion()
    {
        return substr($this->nik, 0, 6);
    }

    public function parseProvinceCityRegion()
    {
        $val = $this->parseRegion();
        return substr($val, 0, 4);
    }

    public function parseProvinceRegion()
    {
        $val = $this->parseRegion();
        return substr($val, 0, 2);
    }

    public function parseCityRegion()
    {
        $val = $this->parseRegion();
        return substr($val, 2, 2);
    }

    public function parseDistrictRegion()
    {
        $val = $this->parseRegion();
        return substr($val, 4, 2);
    }

    public function getProvince()
    {
        return $this->array_get($this->array_get($this->regions, 'provinsi', []), (int)$this->parseProvinceRegion());
    }

    public function provinceFull()
    {
        return [
            'code' => $this->parseProvinceRegion(),
            'name' => $this->getProvince()
        ];
    }

    public function getCity()
    {
        return $this->array_get($this->array_get($this->regions, 'kabkot', []), (int)$this->parseProvinceCityRegion());
    }

    public function cityFull()
    {
        return [
            'code' => $this->parseProvinceCityRegion(),
            'name' => $this->getCity()
        ];
    }

    public function getDistrictWithPostalCode()
    {
        return $this->array_get($this->array_get($this->regions, 'kecamatan', []), (int)$this->parseRegion());
    }

    public function getDistrict()
    {
        $result = $this->getDistrictWithPostalCode();
        return $this->splitDistrict($result);
    }

    public function districtFull()
    {
        return [
            'code' => $this->parseRegion(),
            'name' => $this->getDistrict()
        ];
    }

    public function splitDistrict($val)
    {
        $split = explode(' -- ', $val);
        return $this->array_get($split, 0);
    }

    public function splitPostalCode($val)
    {
        $split = explode(' -- ', $val);
        return $this->array_get($split, 1);
    }

    public function getPostalCode()
    {
        $result = $this->getDistrictWithPostalCode();
        return $this->splitPostalCode($result);
    }

    public function postalCodeFull()
    {
        return [
            'code' => $this->getPostalCode(),
        ];
    }

    /**
     * @return array
     */
    public function fullRegion()
    {
        $this->loadMissingRegion();

        $result = [
            'province' => $this->provinceFull(),
            'city' => $this->cityFull(),
            'district' => $this->districtFull(),
            'postal_code' => $this->postalCodeFull()
        ];
        return $result;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $this->loadMissingRegion();

        return array_merge([
            'valid' => $this->isValid(),
            'nik' => $this->nik,
            'birth_date' => $this->parseBirthToCarbon()->format('d-m-Y'),
            'birth_city' => $this->getCity(),
            'unique_code' => $this->parseRandomNumber(),
            'gender' => $this->parseGender(),
        ], $this->fullRegion());
    }

    /**
     * @param string|null $nik
     * @return array
     */
    public function parse($nik = null)
    {
        return $this->setNIK($nik)->toArray();
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode($this->toArray());
    }

}
