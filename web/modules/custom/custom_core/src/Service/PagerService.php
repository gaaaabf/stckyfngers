<?php

namespace Drupal\custom_core\Service;

class PagerService {

  protected $page;

  protected $items_per_page;

  protected $total_item_count;

  protected $total_pages;

  protected $total_display_pagers;

  protected $one_step;

  public function __construct() {

  }

  public function setPage($page = 1) {
    if (empty($page)) {
      $page = 1;
    }
    $this->page = $page;
  }

  public function setItemsPerPage($items_per_page) {
    $this->items_per_page = $items_per_page;
  }

  public function setTotalItemCount($total_item_count) {
    $this->total_item_count = $total_item_count;
  }

  public function setTotalPages() {
    $this->total_pages = ceil($this->total_item_count / $this->items_per_page);
  }

  public function setTotalDisplayPagers($total_display_pagers) {
    $this->total_display_pagers = $total_display_pagers;
  }

  public function setOneStep($one_step) {

  }

  public function getOffsetLimit() {
    $offset = ($this->page - 1) * $this->items_per_page;
    $results = ['offset' => $offset, 'limit' => $this->items_per_page];

    return $results;
  }

  public function getPagerLinks() {
    $links = [];
    $page = $this->page;

    for ($i = 0; $i != $this->total_pages; $i++) {
      $page_no = $page + $i;

      $links[] = [
        'link_no' => $page_no,
        'link_url' => '?page=' . $page_no, 
      ];

      // if ($this->total_item_count == $page_no) break;
    }

    return $links;
  }

}