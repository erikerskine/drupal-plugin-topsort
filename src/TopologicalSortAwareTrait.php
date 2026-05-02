<?php

declare(strict_types=1);

namespace ErikErskine\DrupalPluginTopsort;

use MJS\TopSort\Implementations\StringSort;

trait TopologicalSortAwareTrait {

  public function findDefinitions(): array {
    $unsorted_definitions = parent::findDefinitions();

    // If there are no plugins at all, don't try to sort them.
    if (empty($unsorted_definitions)) {
      return [];
    }

    // Build a mapping of plugin IDs to their dependencies.
    $mapping = [];
    foreach ($unsorted_definitions as $plugin_id => $definition) {
      if (!isset($mapping[$plugin_id])) {
        $mapping[$plugin_id] = [];
      }
      foreach ($definition['before'] ?? [] as $later_plugin_id) {
        if (!isset($mapping[$later_plugin_id])) {
          $mapping[$later_plugin_id] = [];
        }
        $mapping[$later_plugin_id][] = $plugin_id;
      }
      foreach ($definition['after'] ?? [] as $earlier_plugin_id) {
        if (!isset($mapping[$earlier_plugin_id])) {
          $mapping[$earlier_plugin_id] = [];
        }
        $mapping[$plugin_id][] = $earlier_plugin_id;
      }
    }

    // Get a list of plugin IDs, sorted appropriately.
    $sorter = new StringSort();
    foreach ($mapping as $plugin_id => $plugin_dependencies) {
      $sorter->add($plugin_id, $plugin_dependencies);
    }
    $sorted_plugin_ids = $sorter->sort();

    // The before/after properties are constraints, and it is possible that a
    // plugin declares a non-existent plugin in these constraints.
    // The sorter doesn't know anything about plugins, it just gives us a list
    // of strings. Filter out things which aren't actually plugins.
    $sorted_plugin_ids = array_filter($sorted_plugin_ids, function(string $plugin_id) use ($unsorted_definitions) {
      return isset($unsorted_definitions[$plugin_id]);
    });

    $sorted_definitions = [];
    foreach ($sorted_plugin_ids as $plugin_id) {
      $sorted_definitions[$plugin_id] = $unsorted_definitions[$plugin_id];
    }

    return $sorted_definitions;
  }

}
