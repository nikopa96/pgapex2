<?php

namespace App\Models;

class Template extends Model {
  public function getLoginTemplates() {
    return $this->getPageTemplatesOfType('LOGIN');
  }

  public function getPageTemplates() {
    return $this->getPageTemplatesOfType('NORMAL');
  }

  private function getPageTemplatesOfType($pageType) {
    $connection = $this->getDb()->getConnection();
    $statement = $connection->prepare('SELECT pgapex.f_template_get_page_templates(:pageType)');
    $statement->bindValue(':pageType', $pageType);
    $statement->execute();
    return $statement->fetchColumn();
  }
}