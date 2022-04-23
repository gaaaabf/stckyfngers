<?php

namespace Drupal\custom_core\Service;

class PagerService {

  protected $page;

  protected $items_per_page;

  protected $total_item_count;

  protected $total_pages;

  protected $one_step;

  protected $total_display_pagers = 5;

  /**
   * Set the page
   */
  public function setPage($page = 1) {
    if (empty($page)) {
      $page = 1;
    }
    $this->page = $page;
  }

  /**
   * Set items per page
   */
  public function setItemsPerPage($items_per_page) {
    $this->items_per_page = $items_per_page;
  }

  /**
   * Set the total item count/row
   */
  public function setTotalItemCount($total_item_count) {
    $this->total_item_count = $total_item_count;
  }

  /**
   * Set the total paginations needed
   */
  public function setTotalPages($total_item_count = NULL) {
    if (empty($total_item_count)) {
      $total_item_count = $this->total_item_count;
    }
    $this->total_pages = ceil($total_item_count / $this->items_per_page);
  }

  /**
   * Set the limit paginations to display
   */
  public function setTotalDisplayPagers($total_display_pagers) {
    $this->total_display_pagers = $total_display_pagers;
  }

  /**
   * Set if next/prev should display on the pagination
   */
  public function setOneStep($one_step) {

  }

  /**
   * Fetch the offset and limit for query
   */
  public function getOffsetLimit() {
    $offset = ($this->page - 1) * $this->items_per_page;
    $results = ['offset' => $offset, 'limit' => $this->items_per_page];

    return $results;
  }

  /**
   * Builds the pager links in an array to be rendered on twig
   */
  public function getPagerLinks() {
    $links = [];
    $page = $this->page;

    // Build previous links
    for ($i = 1; $i != $this->total_pages + 1; $i++) {
      $page_no = $i + ($page - $this->total_display_pagers);

      $links[] = [
        'link_no' => $page_no,
        'link_url' => '?page=' . $page_no, 
      ];

      if ($i == $this->total_display_pagers - 1) break;
    }

    // Build current link
    $links[] = [
      'link_no' => $page,
      'link_url' => '?page=' . $page,
      'active' => 'active', 
    ];

    // Build next links
    for ($i = 1; $i != $this->total_pages + 1; $i++) {
      $page_no = $i + $page;

      $links[] = [
        'link_no' => $page_no,
        'link_url' => '?page=' . $page_no, 
      ];

      if ($i == $this->total_display_pagers) break;
    }

    return $links;
  }

}