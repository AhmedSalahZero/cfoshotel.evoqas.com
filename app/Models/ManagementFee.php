<?php

namespace App\Models;

use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;

class ManagementFee extends Model implements AllowsLazyLoading
{


    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];

    public function hospitalitySector()
    {
        return $this->belongsTo(HospitalitySector::class, 'hospitality_sector_id', 'id');
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPayloadAtYear(int $year)
    {
        $payload = $this->payload;
        $payload = arrayToValueIndexes($payload);

        return isset($payload[$year]) ? $payload[$year] : 0;
    }

    public function getPayload()
    {
        $payload = $this->payload;

        return arrayToValueIndexes($payload);
    }

    public function getIdentifier()
    {
        return $this->id;
    }

    public static function getIdentifierName()
    {
        return 'id';
    }

    public function setPayloadAttribute($jsonValue)
    {
        $this->attributes['payload'] = repeatJson($jsonValue, true);
    }
    // 	protected static function replaceIdWithName(array $items)
    // 	{
    // 		$result = [];
    // 		foreach($items as $id=>$items){
    // 			if(is_numeric($id)){
    // 				$result[DepartmentExpense::find($id)->getName()]=$items;
    // 			}else{
    // 				$result[$id] = $items;
    // 			}
    // 		}
    // 		return $result ;
    // 	}
    // 	public static function getNameFromId($id,string $totalNaming = null):string
    // {
    // 	if($id ==0&&$totalNaming){
    // 		return $totalNaming ;
    // 	}

    // 	if(is_numeric($id)){
    // 		return static::where('id',$id)->first()->getName();
    // 	}
    // 	return $id;
    // }

}
