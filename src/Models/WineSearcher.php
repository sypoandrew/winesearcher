<?php
namespace Sypo\Winesearcher\Models;

use Illuminate\Support\Facades\Log;
use Sypo\Winesearcher\Models\WineSearcher;
use Aero\Catalog\Models\Product;
use Aero\Catalog\Models\Variant;

class WineSearcher
{
    #protected $filename = 'winesearcher.xml';
    protected $filename = 'testwinesearcher.xml';
    
    /**
     * Generate the Wine Searcher XML feed
     *
     * @return boolean
     */
    public function call()
    {
        try {
			$arr = [];
			
			$v = new Variant::where('sku', 'LIKE', 'IB%')->where('stock_level', '>', 0)->products()->where('active', 1)->get();
			foreach($v as $variant){
				$p = $variant->product();
				$price = $prices()->where('quantity', 1)->first();
				$vintage = optional($p->tags()->join('tag_groups', 'tag_groups.id', '=', 'tags.tag_group_id')->where("tag_groups.name->{$lang}", 'Vintage')->first())->name;
				$bottle_size = optional($p->tags()->join('tag_groups', 'tag_groups.id', '=', 'tags.tag_group_id')->where("tag_groups.name->{$lang}", 'Bottle Size')->first())->name;
				$case_size = optional($p->tags()->join('tag_groups', 'tag_groups.id', '=', 'tags.tag_group_id')->where("tag_groups.name->{$lang}", 'Case Size')->first())->name;
				$bottle = "{$case_size} x {$bottle_size}";
				
				$arr[] = [
				'name' => $p->name,
				'price' => $price->value,
				'vintage' => optional($p->tags()->join('tag_groups', 'tag_groups.id', '=', 'tags.tag_group_id')->where("tag_groups.name->{$lang}", 'Critic Score')->first())->name,
				'bottle' => $bottle,
				'link' => $p->getUrl(true),
				]
			}
			
			
			$xmlString = '';
			$xmlString .= '<?xml version="1.0" encoding="UTF-8"?><wine-searcher-datafeed><wine-list>';
			foreach($arr as $wine){
				$xmlString .= '<wine><wine-name><![CDATA['.$wine['name'].']]></wine-name><price>'.$wine['price'].'</price><vintage>'.$wine['vintage'].'</vintage><bottle-size>'.$wine['bottle'].'</bottle-size><link>'.$wine['link'].'</link></wine>';
			}
			$xmlString .= '</wine-list></wine-searcher-datafeed>';
			
			
			$dom = new \DOMDocument;
			$dom->preserveWhiteSpace = FALSE;
			$dom->loadXML($xmlString);

			//Save XML as a file
			$dom->save($this->filename);
			
			return true;
		}
		catch(RequestException $e) {
			#Log::warning($e);
			
			$err = new ErrorReport;
			$err->message = $e;
			$err->code = $this->error_code;
			$err->line = __LINE__;
			$err->save();
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
