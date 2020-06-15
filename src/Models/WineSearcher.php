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
				$vintage = optional($p->tags()->join('tag_groups', 'tag_groups.id', '=', 'tags.tag_group_id')->where("tag_groups.name->{$lang}", 'Critic Score')->first())->name;
				$arr[] = [
				'name' => $p->name,
				'price' => $price->value,
				'vintage' => optional($p->tags()->join('tag_groups', 'tag_groups.id', '=', 'tags.tag_group_id')->where("tag_groups.name->{$lang}", 'Critic Score')->first())->name,
				'bottle' => '',
				'link' => $p->getUrl(true),
				]
			}
			
			
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
