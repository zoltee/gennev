<?php
namespace Lib;
class Pagination{
	private $pagination_info;

	const COUNT_ALL = 'All';

	function __construct(){
		$this->reset();
	}

	/**
	 * @return Pagination $this
	 */
	function reset(){
		$this->get_empty();
		$this->pagination_info['items_per_page']=false;
		$this->pagination_info['pages_at_once']=DEFAULT_PAGES_AT_ONCE;
		$this->pagination_info['calculated']=false;
		return $this;
	}

	/**
	 * @var int $nr
	 * @return Pagination $this
	 */
	function set_nr_of_pages($nr){
		if (empty($nr)) return $this;
		$this->pagination_info['pages_at_once']=$nr;
		$this->pagination_info['calculated'] = false;
		$this->calculate();
		return $this;
	}
	/**
	 * @var int $nr
	 * @return Pagination $this
	 */
	function set_items_per_page($nr){
		if($nr===self::COUNT_ALL){
			$nr=false;
		}else{
			//$this->pagination_info['show_all_link']=true;
		}
		$this->pagination_info['items_per_page']=$nr;
		//recalculate when items per page change
		$this->pagination_info['calculated'] = false;
		if(!empty($this->pagination_info['from_item']))$this->set_from_item($this->pagination_info['from_item']);
		if (!empty($this->pagination_info['current_page'])) $this->set_from_page($this->pagination_info['current_page']);
		$this->calculate();
		return $this;
	}

	/**
	 * @return int
	 */
	function get_items_per_page(){
		return $this->pagination_info['items_per_page'];
	}

	/**
	 * @var int $item_number
	 * @return Pagination $this
	 */
	function set_from_item($item_number){
		if($item_number<=1)$item_number=1;
		$this->pagination_info['from_item']=$item_number;
		$this->pagination_info['calculated'] = false;
		if(empty($this->pagination_info['items_per_page']))return $this;
		$this->pagination_info['current_page']=ceil($this->pagination_info['from_item']/$this->pagination_info['items_per_page']);
		$this->pagination_info['from_item']=(($this->pagination_info['current_page']-1)*$this->pagination_info['items_per_page'])+1;
		$this->calculate();
		return $this;
	}

	/**
	 * @var int $page
	 * @return Pagination $this
	 *
	 */
	function set_from_page($page){
		if($page<=1)$page=1;
		$this->pagination_info['current_page']=$page;
		$this->pagination_info['calculated'] = false;
		if(empty($this->pagination_info['items_per_page']))return $this;
		$this->pagination_info['from_item']=(($page-1)*$this->pagination_info['items_per_page'])+1;
		$this->calculate();
		return $this;
	}

	/**
	 * @return int
	 */
	function get_from(){
		return $this->pagination_info['from_item'];
	}

	/**
	 * @var string $base_url
	 * @return Pagination $this
	 */
	function set_base_url($base_url){
		$this->pagination_info['url'] = preg_replace('/(\W)?from=[\d]+/i', '\1', $base_url);
		$this->pagination_info['url'] = str_replace('?&', '?', $this->pagination_info['url']);
		return $this;
	}

	/**
	 * @return string
	 */
	function get_limit_string(){
		if($this->pagination_info['items_per_page']===false) return null;
		return ($this->pagination_info['from_item']-1).",".$this->pagination_info['items_per_page'];
	}

	/**
	 * @var int $total
	 * @return Pagination $this
	 */
	function set_total($total){
		$this->pagination_info['total']=$total;
		if($this->pagination_info['items_per_page']===false)$this->pagination_info['items_per_page']=$total;
		$this->pagination_info['calculated'] = false;
		$this->calculate();
		return $this;
	}

	private function calculate($force=false){
		if(!$force && $this->pagination_info['calculated'])return $this;
		if (empty($this->pagination_info['total'])) return $this;
		if(empty($this->pagination_info['items_per_page'])) $this->pagination_info['items_per_page']=$this->pagination_info['total'];
		$this->pagination_info['current_page']=ceil($this->pagination_info['from_item']/$this->pagination_info['items_per_page']);
		$this->pagination_info['pages']=ceil($this->pagination_info['total']/$this->pagination_info['items_per_page']);
		if($this->pagination_info['current_page']>1){
			$this->pagination_info['prev_nr']=$this->pagination_info['from_item']-$this->pagination_info['items_per_page'];
			$this->pagination_info['prev_page'] = $this->pagination_info['current_page'] -1;
			$this->pagination_info['need_prev']=true;
			$this->pagination_info['need_first']=true;
		}else{
			$this->pagination_info['prev_nr']=1;
			$this->pagination_info['prev_page']=1;
			$this->pagination_info['need_prev']=false;
			$this->pagination_info['need_first']=false;
		}
		if($this->pagination_info['current_page']<$this->pagination_info['pages']){
			$this->pagination_info['next_nr']=$this->pagination_info['from_item']+$this->pagination_info['items_per_page'];
			$this->pagination_info['last_nr']=((ceil($this->pagination_info['total']/$this->pagination_info['items_per_page'])-1)*$this->pagination_info['items_per_page'])+1;
			$this->pagination_info['next_page']=$this->pagination_info['current_page']+1;
			$this->pagination_info['last_page']=$this->pagination_info['pages'];

			$this->pagination_info['need_next']=true;
			$this->pagination_info['need_last']=true;
		}else{
			$this->pagination_info['next_nr']=$this->pagination_info['from_item'];
			$this->pagination_info['next_page']=$this->pagination_info['pages'];
			$this->pagination_info['need_next']=false;
			$this->pagination_info['need_last']=false;
			$this->pagination_info['last_nr']=$this->pagination_info['from_item'];
			$this->pagination_info['last_page']=$this->pagination_info['pages'];
		}
		if($this->pagination_info['current_page'] < $this->pagination_info['pages_at_once']){
			$this->pagination_info['pages_start']=max(1, $this->pagination_info['current_page']-floor($this->pagination_info['pages_at_once']/2));
			$this->pagination_info['pages_end']=min($this->pagination_info['pages'], $this->pagination_info['pages_start'] + $this->pagination_info['pages_at_once']-1);
		}else{
			$this->pagination_info['pages_end']=min($this->pagination_info['pages'], $this->pagination_info['current_page'] + floor($this->pagination_info['pages_at_once']/2));
			$this->pagination_info['pages_start']=max(1,$this->pagination_info['pages_end']-$this->pagination_info['pages_at_once']+1);
		}

		if($this->pagination_info['need_first']||$this->pagination_info['need_prev']||$this->pagination_info['need_next']||$this->pagination_info['need_last']){
			$this->pagination_info['need_pagination']=true;
		}else{
			$this->pagination_info['need_pagination']=false;
		}
		$this->pagination_info['calculated']=true;
		return $this;
	}


	/**
	 * @return boolean
	 */
	function is_needed(){
		$this->calculate();
		return $this->pagination_info['need_pagination'];
	}

	/**
	 * @return array
	 */
	function get_pagination_values(){
		$this->calculate();
		return $this->pagination_info;
	}

	/**
	 * @return array
	 */
	function get_pagination_values_for_pages(){
		$this->calculate2();
		return $this->pagination_info;
	}

	/**
	 * @return array
	 */
	function get_total(){
		$this->calculate();
		return $this->pagination_info['total'];
	}

	/**
	 * @return array
	 */

	function get_empty(){
		$this->pagination_info['url']="";
		$this->pagination_info['current_page']=0;
		$this->pagination_info['pages']=0;
		$this->pagination_info['from_item']=1;
		$this->pagination_info['items_per_page']=1;
		$this->pagination_info['total']=0;
		$this->pagination_info['first_nr']=1;
		$this->pagination_info['prev_nr']=1;
		$this->pagination_info['prev_page']=1;
		$this->pagination_info['next_nr']=1;
		$this->pagination_info['next_page']=1;
		$this->pagination_info['last_nr']=1;
		$this->pagination_info['last_page']=1;
		$this->pagination_info['need_first']=false;
		$this->pagination_info['need_prev']=false;
		$this->pagination_info['need_next']=false;
		$this->pagination_info['need_last']=false;
		$this->pagination_info['need_pagination']=false;
		return $this->pagination_info;
	}
}
