<?php

namespace App\Models;

use App\Http\Request;
use Exception;
use PDO;

class Region extends Model {
  public function getDisplayPointsWithRegions($pageId) {
    $connection = $this->getDb()->getConnection();
    $statement = $connection->prepare('SELECT pgapex.f_region_get_display_points_with_regions(:pageId)');
    $statement->bindValue(':pageId', $pageId, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetchColumn();
  }

  public function getRegion($regionId) {
    $connection = $this->getDb()->getConnection();
    $statement = $connection->prepare('SELECT pgapex.f_region_get_region(:regionId)');
    $statement->bindValue(':regionId', $regionId, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetchColumn();
  }

  public function deleteRegion($regionId) {
    $connection = $this->getDb()->getConnection();
    $statement = $connection->prepare('SELECT pgapex.f_region_delete_region(:regionId)');
    $statement->bindValue(':regionId', $regionId, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetchColumn() === true;
  }

  public function saveHtmlRegion(Request $request) {
    $connection = $this->getDb()->getConnection();
    $statement = $connection->prepare('SELECT pgapex.f_region_save_html_region(:regionId, :pageId, :templateId, :tplDpId, :name, :sequence, :isVisible, :content)');
    $statement->bindValue(':regionId',   $request->getApiAttribute('regionId'),                   PDO::PARAM_INT);
    $statement->bindValue(':pageId',     $request->getApiAttribute('pageId'),                     PDO::PARAM_INT);
    $statement->bindValue(':templateId', $request->getApiAttribute('regionTemplate'),             PDO::PARAM_INT);
    $statement->bindValue(':tplDpId',    $request->getApiAttribute('pageTemplateDisplayPointId'), PDO::PARAM_INT);
    $statement->bindValue(':name',       $request->getApiAttribute('name'),                       PDO::PARAM_STR);
    $statement->bindValue(':sequence',   $request->getApiAttribute('sequence'),                   PDO::PARAM_INT);
    $statement->bindValue(':isVisible',  $request->getApiAttribute('isVisible'),                  PDO::PARAM_BOOL);
    $statement->bindValue(':content',    $request->getApiAttribute('content'),                    PDO::PARAM_STR);
    $statement->execute();
    return $statement->fetchColumn() === true;
  }

  public function saveNavigationRegion(Request $request) {
    $connection = $this->getDb()->getConnection();
    $statement = $connection->prepare('SELECT pgapex.f_region_save_navigation_region(:regionId, :pageId, :templateId, :tplDpId, :name, :sequence, :isVisible, '
                                    . ':navigationType, :navigation, :navigationTemplate, :repeatLastLevel)');
    $statement->bindValue(':regionId',           $request->getApiAttribute('regionId'),                   PDO::PARAM_INT);
    $statement->bindValue(':pageId',             $request->getApiAttribute('pageId'),                     PDO::PARAM_INT);
    $statement->bindValue(':templateId',         $request->getApiAttribute('regionTemplate'),             PDO::PARAM_INT);
    $statement->bindValue(':tplDpId',            $request->getApiAttribute('pageTemplateDisplayPointId'), PDO::PARAM_INT);
    $statement->bindValue(':name',               $request->getApiAttribute('name'),                       PDO::PARAM_STR);
    $statement->bindValue(':sequence',           $request->getApiAttribute('sequence'),                   PDO::PARAM_INT);
    $statement->bindValue(':isVisible',          $request->getApiAttribute('isVisible'),                  PDO::PARAM_BOOL);

    $statement->bindValue(':navigationType',     $request->getApiAttribute('navigationType'),             PDO::PARAM_STR);
    $statement->bindValue(':navigation',         $request->getApiAttribute('navigation'),                 PDO::PARAM_INT);
    $statement->bindValue(':navigationTemplate', $request->getApiAttribute('navigationTemplate'),         PDO::PARAM_INT);
    $statement->bindValue(':repeatLastLevel',    $request->getApiAttribute('repeatLastLevel'),            PDO::PARAM_BOOL);
    $statement->execute();
    return $statement->fetchColumn() === true;
  }

  public function saveReportRegion(Request $request) {
    $connection = $this->getDb()->getConnection();
    $connection->beginTransaction();
    try {
      if (count($request->getApiAttribute('reportColumns')) === 0) {
        throw new Exception('At least one report column is mandatory');
      }

      $statement = $connection->prepare('SELECT pgapex.f_region_save_report_region(:regionId, :pageId, :templateId, :tplDpId, :name, :sequence, :isVisible, '
        . ':reportTemplate, :viewSchema, :viewName, :itemsPerPage, :showHeader, :paginationQueryParameter)');
      $statement->bindValue(':regionId',                 $request->getApiAttribute('regionId'),                   PDO::PARAM_INT);
      $statement->bindValue(':pageId',                   $request->getApiAttribute('pageId'),                     PDO::PARAM_INT);
      $statement->bindValue(':templateId',               $request->getApiAttribute('regionTemplate'),             PDO::PARAM_INT);
      $statement->bindValue(':tplDpId',                  $request->getApiAttribute('pageTemplateDisplayPointId'), PDO::PARAM_INT);
      $statement->bindValue(':name',                     $request->getApiAttribute('name'),                       PDO::PARAM_STR);
      $statement->bindValue(':sequence',                 $request->getApiAttribute('sequence'),                   PDO::PARAM_INT);
      $statement->bindValue(':isVisible',                $request->getApiAttribute('isVisible'),                  PDO::PARAM_BOOL);

      $statement->bindValue(':reportTemplate',           $request->getApiAttribute('reportTemplate'),             PDO::PARAM_INT);
      $statement->bindValue(':viewSchema',               $request->getApiAttribute('viewSchema'),                 PDO::PARAM_STR);
      $statement->bindValue(':viewName',                 $request->getApiAttribute('viewName'),                   PDO::PARAM_STR);
      $statement->bindValue(':itemsPerPage',             $request->getApiAttribute('itemsPerPage'),               PDO::PARAM_INT);
      $statement->bindValue(':showHeader',               $request->getApiAttribute('showHeader'),                 PDO::PARAM_BOOL);
      $statement->bindValue(':paginationQueryParameter', $request->getApiAttribute('paginationQueryParameter'),   PDO::PARAM_STR);
      $statement->execute();
      $regionId = $statement->fetchColumn();

      $statement = $connection->prepare('SELECT pgapex.f_region_delete_report_region_columns(:regionId)');
      $statement->bindValue(':regionId', $regionId, PDO::PARAM_INT);
      $statement->execute();

      $columnStatement = $connection->prepare('SELECT pgapex.f_region_create_report_region_column(:regionId, :viewColumnName, :heading, :sequence, :isTextEscaped)');
      $linkStatement = $connection->prepare('SELECT pgapex.f_region_create_report_region_link(:regionId, :heading, :sequence, :isTextEscaped, :url, :linkText, :attributes)');
      foreach ($request->getApiAttribute('reportColumns') as $reportColumn) {
        if ($reportColumn['attributes']['type'] === 'COLUMN') {
          $columnStatement->bindValue(':regionId',       $regionId,                                    PDO::PARAM_INT);
          $columnStatement->bindValue(':viewColumnName', $reportColumn['attributes']['column'],        PDO::PARAM_STR);
          $columnStatement->bindValue(':heading',        $reportColumn['attributes']['heading'],       PDO::PARAM_STR);
          $columnStatement->bindValue(':sequence',       $reportColumn['attributes']['sequence'],      PDO::PARAM_INT);
          $columnStatement->bindValue(':isTextEscaped',  $reportColumn['attributes']['isTextEscaped'], PDO::PARAM_BOOL);
          $columnStatement->execute();
        } elseif ($reportColumn['attributes']['type'] === 'LINK') {
          $linkStatement->bindValue(':regionId',       $regionId,                                     PDO::PARAM_INT);
          $linkStatement->bindValue(':heading',        $reportColumn['attributes']['heading'],        PDO::PARAM_STR);
          $linkStatement->bindValue(':sequence',       $reportColumn['attributes']['sequence'],       PDO::PARAM_INT);
          $linkStatement->bindValue(':isTextEscaped',  $reportColumn['attributes']['isTextEscaped'],  PDO::PARAM_BOOL);
          $linkStatement->bindValue(':url',            $reportColumn['attributes']['linkUrl'],        PDO::PARAM_BOOL);
          $linkStatement->bindValue(':linkText',       $reportColumn['attributes']['linkText'],       PDO::PARAM_BOOL);
          $linkStatement->bindValue(':attributes',     $reportColumn['attributes']['linkAttributes'], PDO::PARAM_BOOL);
          $linkStatement->execute();
        } else {
          throw new Exception('Unknown column type: ' . $reportColumn['attributes']['type']);
        }
      }
      $connection->commit();
      return true;
    } catch (Exception $e) {
      $connection->rollBack();
    }
    return false;
  }
}