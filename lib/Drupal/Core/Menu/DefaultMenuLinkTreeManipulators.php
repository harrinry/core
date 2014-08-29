<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a couple of menu link tree manipulators.
 *
 * This class provides menu link tree manipulators to:
 * - perform access checking
 * - generate a unique index for the elements in a tree and sorting by it
 * - flatten a tree (i.e. a 1-dimensional tree)
 * - extract a subtree of the given tree according to the active trail
 */
class DefaultMenuLinkTreeManipulators {

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators object.
   *
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(AccessManagerInterface $access_manager, AccountInterface $account) {
    $this->accessManager = $access_manager;
    $this->account = $account;
  }

  /**
   * Performs access checks of a menu tree.
   *
   * Removes menu links from the given menu tree whose links are inaccessible
   * for the current user, sets the 'access' property to TRUE on tree elements
   * that are accessible for the current user.
   *
   * Makes the resulting menu tree impossible to render cache, unless render
   * caching per user is acceptable.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function checkAccess(array $tree) {
    foreach ($tree as $key => $element) {
      // Other menu tree manipulators may already have calculated access, do not
      // overwrite the existing value in that case.
      if (!isset($element->access)) {
        $tree[$key]->access = $this->menuLinkCheckAccess($element->link);
      }
      if ($tree[$key]->access) {
        if ($tree[$key]->subtree) {
          $tree[$key]->subtree = $this->checkAccess($tree[$key]->subtree);
        }
      }
      else {
        unset($tree[$key]);
      }
    }
    return $tree;
  }

  /**
   * Checks access for one menu link instance.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $instance
   *   The menu link instance.
   *
   * @return bool
   *   TRUE if the current user can access the link, FALSE otherwise.
   */
  protected function menuLinkCheckAccess(MenuLinkInterface $instance) {
    if ($this->account->hasPermission('link to any page')) {
      return TRUE;
    }
    // Use the definition here since that's a lot faster than creating a Url
    // object that we don't need.
    $definition = $instance->getPluginDefinition();
    // 'url' should only be populated for external links.
    if (!empty($definition['url']) && empty($definition['route_name'])) {
      $access = TRUE;
    }
    else {
      $access = $this->accessManager->checkNamedRoute($definition['route_name'], $definition['route_parameters'], $this->account);
    }
    return $access;
  }

  /**
   * Generates a unique index and sorts by it.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function generateIndexAndSort(array $tree) {
    $new_tree = array();
    foreach ($tree as $key => $v) {
      if ($tree[$key]->subtree) {
        $tree[$key]->subtree = $this->generateIndexAndSort($tree[$key]->subtree);
      }
      $instance = $tree[$key]->link;
      // The weights are made a uniform 5 digits by adding 50000 as an offset.
      // After $this->menuLinkCheckAccess(), $instance->getTitle() has the
      // localized or translated title. Adding the plugin id to the end of the
      // index insures that it is unique.
      $new_tree[(50000 + $instance->getWeight()) . ' ' . $instance->getTitle() . ' ' . $instance->getPluginId()] = $tree[$key];
    }
    ksort($new_tree);
    return $new_tree;
  }

  /**
   * Flattens the tree to a single level.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function flatten(array $tree) {
    foreach ($tree as $key => $element) {
      if ($tree[$key]->subtree) {
        $tree += $this->flatten($tree[$key]->subtree);
      }
      $tree[$key]->subtree = array();
    }
    return $tree;
  }

  /**
   * Extracts a subtree of the active trail.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   * @param int $level
   *   The level in the active trail to extract.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function extractSubtreeOfActiveTrail(array $tree, $level) {
    // Go down the active trail until the right level is reached.
    while ($level-- > 0 && $tree) {
      // Loop through the current level's elements  until we find one that is in
      // the active trail.
      while ($element = array_shift($tree)) {
        if ($element->inActiveTrail) {
          // If the element is in the active trail, we continue in the subtree.
          $tree = $element->subtree;
          break;
        }
      }
    }
    return $tree;
  }

}
