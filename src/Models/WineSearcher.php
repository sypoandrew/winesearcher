<?php
namespace Sypo\Winesearcher\Models;

use Illuminate\Support\Facades\Log;
use Sypo\Winesearcher\Models\WineSearcher;
use Aero\Catalog\Models\Product;
use Aero\Catalog\Models\Variant;

class WineSearcher
{
    #protected $filename = 'winesearcherfeed.xml';
    protected $filename = 'testwinesearcherfeed.xml';
    
    /**
     * Generate the Wine Searcher XML feed
     *
     * @return boolean
     */
    public function call()
    {
        $lang = config('app.locale');
		
		try {
			$arr = [];
			
			$variants = Variant::where('sku', 'LIKE', '%IB')->where('stock_level', '>', 0)->whereHas('product', static function ($query) {
                $query->where('active', true);
                $query->whereNull('deleted_at');
            })->get();
			
			foreach($variants as $variant){
				$p = $variant->product()->first();
				$price = optional($variant->prices()->where('quantity', 1)->first())->value_inc;
				if($price){
					$price = number_format(($price / 100), 2, '.', '');
				}
				$bottle_size = optional($p->tags()->join('tag_groups', 'tag_groups.id', '=', 'tags.tag_group_id')->where("tag_groups.name->{$lang}", 'Bottle Size')->first())->name;
				$case_size = optional($p->tags()->join('tag_groups', 'tag_groups.id', '=', 'tags.tag_group_id')->where("tag_groups.name->{$lang}", 'Case Size')->first())->name;
				$bottle = "{$case_size} x {$bottle_size}";
				
				$arr[] = [
				'name' => $p->name,
				'price' => $price,
				'vintage' => optional($p->tags()->join('tag_groups', 'tag_groups.id', '=', 'tags.tag_group_id')->where("tag_groups.name->{$lang}", 'Vintage')->first())->name,
				'bottle' => $bottle,
				'link' => $p->getUrl(true),
				];
			}
			
			$xmlString = '';
			$xmlString .= '<?xml version="1.0" encoding="UTF-8"?><wine-searcher-datafeed><wine-list>';
			foreach($arr as $wine){
				$xmlString .= '<wine><wine-name><![CDATA['.$wine['name'].']]></wine-name><price>'.$wine['price'].'</price><vintage>'.$wine['vintage'].'</vintage><bottle-size>'.$wine['bottle'].'</bottle-size><link>'.$wine['link'].'</link></wine>';
			}
			$xmlString .= '</wine-list></wine-searcher-datafeed>';
			
			$dom = new \DOMDocument;
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($xmlString);
			//Save XML as a file
			$dom->save(public_path($this->filename));
			
			return true;
		}
		catch(Exception $e) {
			Log::warning($e);
		}
		
		return false;
    }

    /**
     * @throws \League\Csv\CannotInsertRecord
     */
    protected function saveCsv(): void
    {
        $csv = Writer::createFromPath(storage_path("app/{$this->argument('output')}"), 'w+');
        $csv->insertOne(array_keys(Arr::first($this->rows)));
        $csv->insertAll($this->rows);
    }
}
