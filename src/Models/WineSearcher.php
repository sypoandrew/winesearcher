<?php
namespace Sypo\Winesearcher\Models;

use Illuminate\Support\Facades\Log;
use Sypo\Winesearcher\Models\WineSearcher;
use Aero\Catalog\Models\Product;
use Aero\Catalog\Models\Variant;

class WineSearcher
{
    protected $filename = 'winesearcherfeed.xml';
    protected $lang;
    protected $name_limit = 160;
    
    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->lang = config('app.locale');
    }
    
    /**
     * Generate the Wine Searcher XML feed
     *
     * @return boolean
     */
    public function call()
    {
        try {
			$arr = [];
			
			$variants = Variant::where('variants.stock_level', '>', 0)->join('attribute_variant as av', 'variants.id', 'av.variant_id')->join('attributes', 'attributes.id', 'av.attribute_id')->join('attribute_groups', 'attribute_groups.id', 'attributes.attribute_group_id')->where("attribute_groups.name->{$this->lang}", 'Duty Status')->whereIn("attributes.name->{$this->lang}", ['Bond', 'En Primeur'])->whereHas('product', static function ($query) {
                $query->where('active', true)->whereNull('deleted_at');
            })->get();
            
			foreach($variants as $variant){
				$p = $variant->product()->first();
				$price = optional($variant->prices()->where('quantity', 1)->first())->value_inc;
				if($price){
					$price = number_format(($price / 100), 2, '.', '');
				}
				$bottle_size = $this->getTag($p, 'Bottle Size');
				$case_size = $this->getTag($p, 'Case Size');
				
				if($case_size){
					$bottle = "{$case_size} x {$bottle_size}";
				}
				else{
					$bottle = $bottle_size;
				}
				
				
				$country = $this->getTag($p, 'Country');
				$region = $this->getTag($p, 'Region');
				$subregion = $this->getTag($p, 'Sub Region');
				$colour = $this->getTag($p, 'Colour');
				$winetype = $this->getTag($p, 'Wine Type');
				
				if($country){
					$country = ', ' . $country;
				}
				if($subregion){
					$subregion = ', ' . $subregion;
				}
				if($region){
					$region = ', ' . $region;
				}
				if($colour){
					$colour = ', ' . $colour;
				}
				if($winetype){
					$winetype = ', ' . $winetype;
				}
				
				$name = $p->name . $country . $region . $subregion . $colour . $winetype;
				if(strlen($name) > $this->name_limit){
					//remove subregion
					$name = $p->name . $country . $region . $colour . $winetype;
				}
				if(strlen($name) > $this->name_limit){
					//remove region
					$name = $p->name . $country . $colour . $winetype;
				}
				if(strlen($name) > $this->name_limit){
					//remove wine type
					$name = $p->name . $country . $colour;
				}
				if(strlen($name) > $this->name_limit){
					//sod it - just concat
					$name = substr($name, 0, $this->name_limit);
				}
				
				
				$arr[] = [
				'name' => $name,
				'price' => $price,
				'vintage' => $this->getTag($p, 'Vintage'),
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
     * Get the tag value, if available
     * 
     * @param Aero\Catalog\Models\Product $product
     * @param string $tag_group_name
     * 
     * @return null|string
     */
    protected function getTag(\Aero\Catalog\Models\Product $product, $tag_group_name)
    {
        return optional($product->tags()->join('tag_groups', 'tag_groups.id', '=', 'tags.tag_group_id')->where("tag_groups.name->{$this->lang}", $tag_group_name)->first())->name;
    }
}
