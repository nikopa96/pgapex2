'use strict';
(function (window) {
  let module = window.angular.module('pgApexApp.page');

  function ManageDetailViewRegionController($scope, $location, $routeParams, regionService, pageService,
                                            templateService, databaseService, formErrorService) {
    this.$scope = $scope;
    this.$location = $location;
    this.$routeParams = $routeParams;
    this.regionService = regionService;
    this.pageService = pageService;
    this.templateService = templateService;
    this.databaseService = databaseService;
    this.formErrorService = formErrorService;

    this.init();
  }

  ManageDetailViewRegionController.prototype.init = function () {
    this.$scope.detailViewAppId = this.getApplicationId();

    this.$scope.reportNames = {
      'title': 'region.reportColumns',
      'entityName': 'reportColumns',
      'attributeTitle': 'region.addReportColumn',
      'formName': 'reportColumnsForm'
    };

    this.$scope.detailViewNames = {
      'title': 'region.detailViewColumns',
      'entityName': 'detailViewColumns',
      'attributeTitle': 'region.addDetailViewColumn',
      'formName': 'detailViewColumnsForm'
    };

    this.$scope.region = {
      'reportShowHeader': true,
      'reportItemsPerPage': 15,
      'reportColumns': [],
      'detailViewColumns': []
    };

    this.$scope.mode = this.isCreatePage() ? 'create' : 'edit';
    this.$scope.changeViewColumns = this.changeViewColumns.bind(this);
    this.$scope.saveRegion = this.saveRegion.bind(this);
    this.$scope.formError = this.formErrorService.empty();

    this.$scope.trackView = function(view) {
      if (!view || !view.attributes) { return view; }
      return view.attributes.schema + '.' + view.attributes.name;
    }.bind(this);

    this.initViewsWithColumns();
    this.initRegionTemplates();
    this.initReportLinkTemplates();
    this.initDetailViewTemplates();
    this.initAvailablePages();
  };

  ManageDetailViewRegionController.prototype.getApplicationId = function() {
    return this.$routeParams.applicationId ? parseInt(this.$routeParams.applicationId) : null;
  };

  ManageDetailViewRegionController.prototype.isCreatePage = function () {
    return this.$location.path().endsWith('/create');
  };

  ManageDetailViewRegionController.prototype.isEditPage = function() {
    return this.$location.path().endsWith('/edit');
  };

  ManageDetailViewRegionController.prototype.initViewsWithColumns = function() {
    this.databaseService.getViewsWithColumns(this.$scope.detailViewAppId).then(function (response) {
      this.$scope.viewsWithColumns = response.getDataOrDefault([]);
      this.setViewColumns();
    }.bind(this));
  };

  ManageDetailViewRegionController.prototype.initRegionTemplates = function() {
    this.templateService.getRegionTemplates().then(function (response) {
      this.$scope.regionTemplates = response.getDataOrDefault([]);
    }.bind(this));
  };

  ManageDetailViewRegionController.prototype.initReportLinkTemplates = function() {
    this.templateService.getReportLinkTemplates().then(function (response) {
      this.$scope.reportTemplates = response.getDataOrDefault([]);
    }.bind(this));
  };

  ManageDetailViewRegionController.prototype.initDetailViewTemplates = function() {
    this.templateService.getDetailViewTemplates().then(function (response) {
      this.$scope.detailViewTemplates = response.getDataOrDefault([]);
    }.bind(this));
  };

  ManageDetailViewRegionController.prototype.initAvailablePages = function() {
    this.pageService.getPages(this.$scope.detailViewAppId).then(function (response) {
      this.$scope.pages = response.getDataOrDefault([]);
    }.bind(this));
  };

  ManageDetailViewRegionController.prototype.changeViewColumns = function() {
    this.setViewColumns();
    this.resetUniqueIdSelection();
    this.resetColumnsSelection();
  };

  ManageDetailViewRegionController.prototype.setViewColumns = function() {
    if (!this.$scope.region.view) { return; }
    const view = this.$scope.viewsWithColumns.filter(function (view) {
      return view.attributes.schema === this.$scope.region.view.attributes.schema &&
        view.attributes.name === this.$scope.region.view.attributes.name;
    }.bind(this));
    if (view.length > 0) {
      this.$scope.viewColumns = view[0].attributes.columns;
    }
  };

  ManageDetailViewRegionController.prototype.resetUniqueIdSelection = function() {
    this.$scope.region.uniqueId = '';
  };

  ManageDetailViewRegionController.prototype.resetColumnsSelection = function() {
    this.$scope.region.reportColumns.forEach(function (reportColumn) {
      if (reportColumn.attributes.type === 'COLUMN')  {
        reportColumn.attributes.column = '';
      }
    });

    this.$scope.region.detailViewColumns.forEach(function (reportColumn) {
      if (reportColumn.attributes.type === 'COLUMN')  {
        reportColumn.attributes.column = '';
      }
    });
  };

  ManageDetailViewRegionController.prototype.getRegionId = function() {
    return this.$routeParams.regionId ? parseInt(this.$routeParams.regionId) : null;
  };

  ManageDetailViewRegionController.prototype.getPageId = function() {
    return this.$routeParams.pageId ? parseInt(this.$routeParams.pageId) : null;
  };

  ManageDetailViewRegionController.prototype.getDisplayPoint = function() {
    return this.$routeParams.displayPoint ? parseInt(this.$routeParams.displayPoint) : null;
  };

  ManageDetailViewRegionController.prototype.getReportColumns = function() {
    return this.$scope.region.reportColumns.map(function(reportColumn) {
      if (reportColumn.attributes.type === 'COLUMN') {
        return {
          'type': 'report-column',
          'attributes': {
            'type': reportColumn.attributes.type,
            'column': reportColumn.attributes.column,
            'heading': reportColumn.attributes.heading,
            'isTextEscaped': reportColumn.attributes.isTextEscaped || false,
            'sequence': reportColumn.attributes.sequence
          }
        };
      } else {
        return {
          'type': 'report-column',
          'attributes': {
            'type': reportColumn.attributes.type,
            'heading': reportColumn.attributes.heading,
            'isTextEscaped': reportColumn.attributes.isTextEscaped || false,
            'sequence': reportColumn.attributes.sequence,
            'linkText': reportColumn.attributes.linkText,
            'linkUrl': reportColumn.attributes.linkUrl,
            'linkAttributes': reportColumn.attributes.linkAttributes || null
          }
        };
      }
    });
  };

  ManageDetailViewRegionController.prototype.getDetailViewColumns = function() {
    return this.$scope.region.detailViewColumns.map(function(detailViewColumn) {
      if (detailViewColumn.attributes.type === 'COLUMN') {
        return {
          'type': 'detailview-column',
          'attributes': {
            'type': detailViewColumn.attributes.type,
            'column': detailViewColumn.attributes.column,
            'heading': detailViewColumn.attributes.heading,
            'isTextEscaped': detailViewColumn.attributes.isTextEscaped || false,
            'sequence': detailViewColumn.attributes.sequence
          }
        };
      } else {
        return {
          'type': 'detailview-column',
          'attributes': {
            'type': detailViewColumn.attributes.type,
            'heading': detailViewColumn.attributes.heading,
            'isTextEscaped': detailViewColumn.attributes.isTextEscaped || false,
            'sequence': detailViewColumn.attributes.sequence,
            'linkText': detailViewColumn.attributes.linkText,
            'linkUrl': detailViewColumn.attributes.linkUrl,
            'linkAttributes': detailViewColumn.attributes.linkAttributes || null
          }
        };
      }
    });
  };

  ManageDetailViewRegionController.prototype.saveRegion = function () {
    this.regionService.saveReportAndDetailViewRegion(
      this.$scope.region.view.attributes.schema,
      this.$scope.region.view.attributes.name,
      this.$scope.region.uniqueId,
      this.getRegionId(),
      this.$scope.region.reportName,
      this.$scope.region.reportSequence,
      this.$scope.region.reportRegionTemplate,
      this.$scope.region.reportIsVisible,
      this.$scope.region.reportTemplate,
      this.$scope.region.reportShowHeader,
      this.$scope.region.reportItemsPerPage,
      this.$scope.region.reportPaginationQueryParameter,
      this.getPageId(),
      null,
      this.$scope.region.detailViewName,
      this.$scope.region.detailViewSequence,
      this.$scope.region.detailViewRegionTemplate,
      this.$scope.region.detailViewIsVisible,
      this.$scope.region.detailViewTemplate,
      this.$scope.region.detailViewPageId,
      this.getReportColumns(),
      this.getDetailViewColumns(),
      this.getDisplayPoint()
    ).then(this.handleSaveResponse.bind(this));
  };

  ManageDetailViewRegionController.prototype.handleSaveResponse = function(response) {
    if (!response.hasErrors()) {
      this.$location.path('/application-builder/app/' + this.getApplicationId() + '/pages/' + this.getPageId() +
        '/regions');
    } else {
      this.$scope.formError = this.formErrorService.parseApiResponse(response);
    }
  };

  function init() {
    module.controller('pgApexApp.region.ManageDetailViewRegionController', ['$scope', '$location', '$routeParams',
      'regionService', 'pageService', 'templateService', 'databaseService', 'formErrorService', ManageDetailViewRegionController]);
  }

  init();
})(window);