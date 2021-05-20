const EventsController2 = (
  $scope,
  $window,
  $rootScope,
  $routeParams,
  $cookieStore,
  $location,
  httpServiceInterceptor,
  eventAPIservice2,
  urlBase,
  epicoreMode,
  epicoreVersion,
  Upload,
  $timeout,
  epicoreStartDate,
) => {
  const http = httpServiceInterceptor.http;
  $scope.mobile = epicoreMode == 'mobile' ? true : false;
  $scope.epicore_version = epicoreVersion;
  $scope.isRouteLoading = true;
  $scope.eventsList = [];
  $scope.isShowNotScoredEvents = false;
  $scope.userInfo = $cookieStore.get('epiUserInfo');
  $scope.id = $routeParams.id ? $routeParams.id : null;
  $scope.allFETPs = $routeParams.response_id ? false : true;
  $scope.rfiOrderByValue = 'iso_create_date';
  // if we're on the closed requests page
  $scope.onOpen = $location.path().indexOf('/closed') > 0 ? false : true;
  $scope.changeStatusText = !$scope.onOpen ? 'Re open' : 'Close';
  $scope.changeStatusType = !$scope.onOpen ? 'reopen' : 'close';

  if (!$scope.onOpen) {
    $scope.rfiOrderByValue = 'iso_action_date';
  }
  // check if public dashboard
  $scope.publicDashboard =
    $location.path().indexOf('/events_public') >= 0 ? true : false;
  $scope.isPublicArticleView =
    $location.path().indexOf('/articles') >= 0 ? true : false;
  $scope.anonymous_disabled = false;
  if (!$scope.formData) {
    $scope.formData = {};
  }
  $scope.validResponses = 0;

  $scope.eventType = 'AO';

  $rootScope.dashboardType = 'MR';

  $scope.cbsuffix = Date.now();

  // get list of months for selecting event month
  const dateStart = moment(epicoreStartDate);
  const dateEnd = moment(); // now
  const timeValues = [];
  let i = 0;
  while (dateEnd > dateStart || dateStart.format('M') === dateEnd.format('M')) {
    timeValues.push({
      name: dateStart.format('YYYY-MMMM'),
      value: dateStart.format('YYYY-MM'),
    });
    dateStart.add(1, 'month');
    i++;
  }

  timeValues.push({name: 'All', value: 'all'});
  timeValues.push({name: 'Most Recent', value: 'recent'});
  $scope.event_months = timeValues.reverse();
  $scope.selected_month = timeValues[0];

  // get events for selected month
  $scope.getEventMonth = function(month) {
    let start_date = '';
    let end_date = '';
    let num_events = 'all';
    if (month.value == 'all') {
      start_date = moment(epicoreStartDate).format('YYYY-MM-DD');
      end_date = moment().format('YYYY-MM-DD'); // now
    } else if (month.value == 'recent') {
      start_date = moment().subtract(3, 'months').format('YYYY-MM-DD'); // one month ago
      end_date = moment().format('YYYY-MM-DD'); // now
      num_events = 10;
    } else {
      start_date = moment(month.value + '-01').format('YYYY-MM-DD'); // selected month
      end_date = moment(month.value + '-01')
        .add(1, 'month')
        .format('YYYY-MM-DD'); // next month
    }

    getAllEvents(start_date, end_date, num_events);
    $scope.isShowNotScoredEvents = false;
  };

  // upload response files
  $scope.ufiles = [];
  $scope.uploadFiles = function(files, errFiles) {
    $scope.files = files;
    $scope.errFiles = errFiles;

    let i = 1;
    angular.forEach(files, function(file) {
      $scope.isRouteLoading = true;
      file.upload = Upload.upload({
        url: 'scripts/uploadfile.php',
        data: {
          file: file,
          event_id: $scope.id
        },
        method: 'POST',
      });

      file.upload.then(
        function(response) {
          // save uploaded file names
          $scope.ufiles.push({
            filename: response.data.filename,
            savefilename: response.data.savefilename,
          });
          // turn off spinner
          if (i++ >= files.length) {
            $scope.isRouteLoading = false;
          }

          $timeout(function() {
            file.result = response.data;
          });
        },
        function(response) {
          if (response.status > 0) {
            $scope.errorMsg = response.status + ': ' + response.data;
          }
        },
        function(evt) {
          file.progress = Math.min(
            100,
            parseInt((100.0 * evt.loaded) / evt.total),
          );
        },
      );
    });
  };

  const removeItem = function(file) {
    const index = $scope.ufiles
      .map(function(item) {
        return item.savefilename;
      })
      .indexOf(file);
    $scope.ufiles.splice(index, 1);
  };

  $scope.removeFile = function(file) {
    http({
      url: urlBase + 'scripts/removefile.php',
      method: 'POST',
      data: {filename: file},
    }).then(function successCallback() {
      removeItem(file);
    });
  };

  $scope.publicEvents = function(event) {
    return (
      event.outcome === 'VP' || event.outcome === 'VN' || event.outcome === 'UP'
    );
  };

  $scope.publicArticle = function(eventID) {
    $window.open('#/events_public/articles/' + eventID, '_self');
  };

  $scope.getPublicEventsByID = function() {
    $scope.isRouteLoading = true;
    const article_id = localStorage.getItem('articleID');
    eventAPIservice2.getEvents(article_id).then(function successCallback(res) {
      const response = res.data;
      $scope.isRouteLoading = false;
      $scope.eventsListPublic = response.EventsList;

      if ($scope.eventsListPublic['error-message']) {
        $timeout(() => {
          alert($scope.eventsListPublic['error-message']);
          return;
        });
      }

      let outcome = 'Pending';

      if ($scope.eventsListPublic.outcome == 'VP') {
        outcome = 'Verified (positive)';
      } else if ($scope.eventsListPublic.outcome == 'VN') {
        outcome = 'Verified (negative)';
      } else if ($scope.eventsListPublic.outcome == 'UV') {
        outcome = 'Unverified';
      } else if ($scope.eventsListPublic.outcome == 'UP') {
        outcome = 'Updated (positive)';
      } else if ($scope.eventsListPublic.outcome == 'NU') {
        outcome = 'Updated (negative)';
      }

      $scope.cd = $scope.eventsListPublic.history[0].date;
      $scope.closureDate = $scope.cd.split(' ')[0];
      $scope.od = $scope.eventsListPublic.create_date;
      $scope.openDate = $scope.od.split(' ')[0]; // (to remove time)
      $scope.event_outcome = outcome;
      // $scope.eventTitle = $scope.modifiedEventTitle
      $scope.eventTitle = $scope.eventsListPublic.title;
      $scope.phe_description = $scope.eventsListPublic.phe_description;
      $scope.phe_additional = $scope.eventsListPublic.phe_additional;
      $scope.initialSource =
        $scope.eventsListPublic.source +
        ' : ' +
        $scope.eventsListPublic.source_details;

      $scope.$digest();
    });
  };

  // get events for public dashboard for Responders view
  $scope.getEvents2 = function(dbtype) {
    $scope.isRouteLoading = false;
    $rootScope.dashboardType = dbtype;
    if (dbtype == 'PR' && !$scope.eventsListPublic) {
      $scope.isRouteLoading = true;
      const end_date = moment().format('YYYY-MM-DD'); // now
      const start_date = moment().subtract(2, 'months').format('YYYY-MM-DD'); // 2 months ago
      eventAPIservice2
        .getEvents($scope.id, start_date, end_date)
        .then(function successCallback(res) {
          const response = res.data;
          $scope.isRouteLoading = false;
          $scope.eventsListPublic = response.EventsList;
          if ($scope.eventsListPublic.purpose) {
            $scope.outcomePublic = {};
            $scope.outcomePublic.phe_purpose = 'N';
            if ($scope.eventsListPublic.purpose.indexOf('Verification') >= 0) {
              $scope.outcomePublic.phe_purpose = 'V';
            } else if ($scope.eventsListPublic.purpose.indexOf('Update') >= 0) {
              $scope.outcomePublic.phe_purpose = 'U';
            }
            $scope.summaryPublic = {};
            $scope.summaryPublic.phe_title = $scope.eventsListPublic.title;
          }
        });
    }
  };

  const getAllEvents = function(start_date, end_date, num_events = 'all') {
    $scope.isRouteLoading = true;

    $scope.eventsList = [];
    eventAPIservice2
      .getEvents($scope.id, start_date, end_date)
      .then(function successCallback(res) {
        const response = res.data;
        $scope.isRouteLoading = false;
        if (typeof $scope.userinfo != 'undefined') {
          $scope.isOrganization = $scope.userInfo.fetp_id > 0 ? false : true;
          // if RFI requester is the logged in user or of same org, they get different action items
          if (response.EventsList != null) {
            // $scope.isAuthorizedToFollowup = $scope.userInfo.organization_id == response.EventsList.org_requester_id ? true : false;
            $scope.isAuthorizedToFollowup =
              $scope.userInfo.organization_id ==
                response.EventsList.org_requester_id ||
              $scope.userInfo.superuser ?
                true :
                false;
            $scope.changeStatusText =
              response.EventsList.estatus == 'C' ? 'Re open' : 'Close';
            $scope.changeStatusType =
              response.EventsList.estatus == 'C' ? 'reopen' : 'close';
            $scope.isAuthorizedFETP = false;
            $scope.isRequester =
              response.EventsList.requester_id == $scope.userInfo.uid ?
                true :
                false;
            if (
              response.EventsList.fetp_ids != null &&
              response.EventsList.fetp_ids.indexOf($scope.userInfo.fetp_id) !=
                -1
            ) {
              $scope.isAuthorizedFETP = true;
            }
            if (response.EventsList.fetp_ids) {
              $scope.num_fetp = response.EventsList.fetp_ids.length;
            }

            $scope.eventsList = response.EventsList;
            $scope.filePreview = response.EventsList.filePreview ?
              response.EventsList.filePreview :
              '';

            if (num_events == 'all') {
              $scope.eventsList.all = response.EventsList.all;
              $scope.eventsList.other = response.EventsList.other;
            } else {
              if (response.EventsList.all) {
                $scope.eventsList.all = response.EventsList.all.slice(
                  0,
                  num_events,
                );
              }
              if (response.EventsList.other) {
                $scope.eventsList.other = response.EventsList.other.slice(
                  0,
                  num_events,
                );
              }
            }

            if ($scope.eventsList.purpose) {
              $scope.outcome = {};
              $scope.outcome.phe_purpose = 'N';
              if ($scope.eventsList.purpose.indexOf('Verification') >= 0) {
                $scope.outcome.phe_purpose = 'V';
              } else if ($scope.eventsList.purpose.indexOf('Update') >= 0) {
                $scope.outcome.phe_purpose = 'U';
              }
              $scope.summary = {};
              $scope.summary.phe_title = $scope.eventsList.title;
              $scope.summary.phe_description =
                $scope.eventsList.phe_description;
              $scope.summary.phe_additional = $scope.eventsList.phe_additional;
              $scope.summary.outcome = $scope.eventsList.outcome;
            }
          }
          // ////// public events
        } else if (typeof $scope.userinfo == 'undefined') {
          $scope.eventsList = response.EventsList;
          if (num_events != 'all') {
            const all_events = response.EventsList.all;
            const public_events = [];
            all_events.forEach(function(event) {
              if ($scope.publicEvents(event)) {
                public_events.push(event);
              }
            });
            $scope.eventsList.all = public_events.splice(0, num_events);
          }

          if ($scope.eventsList.purpose) {
            $scope.outcome = {};
            $scope.outcome.phe_purpose = 'N';
            if ($scope.eventsList.purpose.indexOf('Verification') >= 0) {
              $scope.outcome.phe_purpose = 'V';
            } else if ($scope.eventsList.purpose.indexOf('Update') >= 0) {
              $scope.outcome.phe_purpose = 'U';
            }
            $scope.summary = {};
            $scope.summary.phe_title = $scope.eventsList.title;
          }
        }

        // today's date
        const today = new Date();
        const dd = today.getDate();
        const mm = today.getMonth(); // January is 0!
        const yyyy = today.getFullYear();
        const month = [
          'January',
          'February',
          'March',
          'April',
          'May',
          'June',
          'July',
          'August',
          'September',
          'October',
          'November',
          'December',
        ];
        $scope.today_date = dd + '-' + month[mm] + '-' + yyyy;

        // get response
        $scope.response_text = '';
        if ($routeParams.response_id) {
          const formData = {};
          formData['response_id'] = $routeParams.response_id;
          http({
            url: urlBase + 'scripts/getresponse.php',
            method: 'POST',
            data: formData,
          }).then(function successCallback(res) {
            const respdata = res.data;
            $scope.response_text = respdata['response'];
            $scope.responder_id = respdata['responder_id'];
            $scope.permission_id = respdata['response_permission_id'];
          });
        }

        // count unrated responses in closed events
        if ($scope.onOpen) {
          $scope.listofEventIdsToDisplay = response.numNotRatedResponses ?
            response.numNotRatedResponses[1][0] :
            [];
          $scope.num_notrated_responses = response.numNotRatedResponses ?
            response.numNotRatedResponses[0] :
            0;
          $scope.rfiOrderByValue = 'iso_create_date';
        } else if ($scope.eventsList) {
          $scope.rfiOrderByValue = 'iso_action_date';
          for (var n in $scope.eventsList.yours) {
            $scope.num_notrated_responses += parseInt(
              $scope.eventsList.yours[n].num_notrated_responses,
            );
          }
        }

        // count responses with content
        for (var h in $scope.eventsList.history) {
          if (
            $scope.eventsList.history[h].permission !== '0' &&
            $scope.eventsList.history[h].type == 'Member Response' &&
            $scope.userInfo.uid
          ) {
            $scope.validResponses++;
          }
        }

        // check for active search
        $scope.activeSearch = false;
        for (var h in $scope.eventsList.history) {
          if (
            $scope.eventsList.history[h].permission == '4' &&
            $scope.eventsList.history[h].type == 'Member Response' &&
            $scope.eventsList.history[h].fetp_id == $scope.userInfo.fetp_id
          ) {
            $scope.activeSearch = true;
          }
        }

        // check unclosed RFIs with no activity in the last two weeks
        Date.prototype.yyyymmdd = function() {
          const yyyy = this.getFullYear().toString();
          const mm = (this.getMonth() + 1).toString(); // getMonth() is zero-based
          const dd = this.getDate().toString();
          return (
            yyyy +
            '-' +
            (mm[1] ? mm : '0' + mm[0]) +
            '-' +
            (dd[1] ? dd : '0' + dd[0])
          ); // padding
        };
        const d = new Date();
        $scope.date = d.setDate(d.getDate() - 14); // now minus 14 days
        $scope.unclosed = 0;
        for (var n in $scope.eventsList.yours) {
          const newdate = $scope.eventsList.yours[n].num_followups[0].iso_date;
          if (newdate < d.yyyymmdd()) {
            $scope.unclosed++;
          }
        }
        $scope.isRouteLoading = false;

        $scope.eventsListAll = angular.copy($scope.eventsList['all']);

        $scope.$digest();
      });
  };

  $scope.showNotScoredEvents = function() {
    if (!$scope.eventsList['all']) {
      return;
    }
    $scope.isShowNotScoredEvents = !$scope.isShowNotScoredEvents;
    if ($scope.isShowNotScoredEvents) {
      $scope.eventsList['all'] = $scope.eventsList['all'].filter(function(
        event,
      ) {
        return !event.metric_score;
      });
    } else {
      $scope.eventsList['all'] = $scope.eventsListAll;
    }
  };

  // get most recent events for public dashboard
  // get all events on load for open events
  // get events for current month for closed events

  // There were 2 API(s) fired for /articles/ID route. We do not need the getAllEvents here...
  // fired for publicView of article - getPublicEventsByID() is used.
  if ($scope.publicDashboard && $scope.isPublicArticleView) return;

  if ($scope.publicDashboard) {
    var end_date = moment().format('YYYY-MM-DD'); // now
    var start_date = moment().subtract(3, 'months').format('YYYY-MM-DD'); // 3 month ago
    getAllEvents(start_date, end_date, 10);
  } else if ($scope.onOpen) {
    getAllEvents(
      epicoreStartDate,
      moment().add(1, 'days').format('YYYY-MM-DD'),
    );
  } else {
    end_date = moment().format('YYYY-MM-DD'); // now
    start_date = moment().subtract(1, 'months').format('YYYY-MM-DD'); // one month ago
    getAllEvents(start_date, end_date, 10);
  }

  $scope.displaySavingText = false;
  const save_metrics_debounce = 1000;
  let save_metrics_timeout;
  let prev_metric_data = {};

  $scope.updateRFIMetrics = function(field, event) {
    if (
      field === 'metric_score' &&
      !(event.metric_score || event.metric_score > 2)
    ) {
      alert('Score cannot be more than 2');
      return;
    }

    const metric_data = {
      event_metrics_id: event.event_metrics_id,
      score: event.metric_score,
      creation: event.metric_creation,
      notes: event.metric_notes,
      action: event.metric_action,
      event_id: event.event_id,
    };

    if (angular.equals(metric_data, prev_metric_data)) {
      return;
    }

    prev_metric_data = metric_data;

    $scope.displaySavingText = true;

    if (save_metrics_timeout) {
      $timeout.cancel(save_metrics_timeout);
    }

    save_metrics_timeout = $timeout(function() {
      http({
        url: urlBase + 'scripts/updatemetrics.php',
        method: 'POST',
        data: metric_data,
      }).then(
        function successCallback() {
          $scope.displaySavingText = false;
        },
        function errorCallback() {
          $scope.displaySavingText = false;
        },
      );
    }, save_metrics_debounce);
  };

  $scope.sendFollowup = function(formData, isValid) {
    if (isValid) {
      $scope.submitDisabled = true;
      formData['event_id'] = $routeParams.id;
      if ($routeParams.id) {
        var eid = $routeParams.id;
      }
      if ($routeParams.response_id) {
        formData['response_id'] = $routeParams.response_id;
      }
      http({
        url: urlBase + 'scripts/sendfollowup2.php',
        method: 'POST',
        data: formData,
      }).then(function successCallback() {
        $scope.submitDisabled = false;
        $location.path('/success/3/' + eid);
      });
    }
  };

  $scope.changeRequestStatus = function(formData, thestatus, isValid) {
    // count responses assessed as useful,used in promed, or not useful when closing an RFI
    // only for responses with content
    const useful_rids = [];
    const usefulpromed_rids = [];
    const notuseful_rids = [];

    if (
      isValid &&
      (thestatus == 'Close' || thestatus == 'Update') &&
      $scope.validResponses > 0
    ) {
      for (const h in $scope.eventsList.history) {
        const h_rid = $scope.eventsList.history[h].response_id;
        const h_type = $scope.eventsList.history[h].type;
        const h_fetp_id = $scope.eventsList.history[h].fetp_id;
        const h_orgid = $scope.eventsList.history[h].organization_id;
        const h_useful = $scope.eventsList.history[h].useful;
        const h_perm = $scope.eventsList.history[h].permission;
        if (
          h_type == 'Member Response' &&
          h_perm !== '0' &&
          h_perm !== '4' &&
          ($scope.userInfo.uid || h_fetp_id == $scope.userInfo.fetp_id) &&
          (h_orgid == $scope.userInfo.organization_id ||
            $scope.userInfo.superuser)
        ) {
          if (h_useful === null) {
            alert('Please assess all member responses.');
            $scope.close_message = 'Please assess all member responses.';
            return false;
          } else if (h_useful === '1') {
            useful_rids.push(h_rid); // save useful response_ids
          } else if (h_useful === '2') {
            usefulpromed_rids.push(h_rid); // save useful promed response_ids
          } else {
            notuseful_rids.push(h_rid); // save not useful response_ids
          }
        }
      }
    }
    if (isValid) {
      $scope.submitDisabled = true;
      formData['event_id'] = $routeParams.id;
      formData['thestatus'] = thestatus;
      formData['useful_rids'] = useful_rids.toString();
      formData['usefulpromed_rids'] = usefulpromed_rids.toString();
      formData['notuseful_rids'] = notuseful_rids.toString();

      if (thestatus == 'Close') {
        formData['phe_outcome'] = $scope.outcome.answer;
        formData['phe_title'] = $scope.summary.phe_title;
        formData['phe_description'] = $scope.summary.phe_description;
        formData['phe_additional'] = $scope.summary.phe_additional;
      } else if (thestatus == 'Summary') {
        formData['phe_outcome'] = $scope.summary.outcome;
        formData['phe_title'] = $scope.summary.phe_title;
        formData['phe_description'] = $scope.summary.phe_description;
        formData['phe_additional'] = $scope.summary.phe_additional;
      }

      formData['condition_details'] = $scope.eventsList.condition_details;

      http({
        url: urlBase + 'scripts/changestatus2.php',
        method: 'POST',
        data: formData,
      }).then(function successCallback(res) {
        const data = res.data;
        if (data['status'] === 'success') {
          $scope.submitDisabled = false;
          let pathid = 4;
          if (thestatus == 'Update') {
            pathid = 8;
          } else if (thestatus == 'Reopen') {
            pathid = 5;
          } else if (thestatus == 'Summary') {
            pathid = 9;
          } else {
            // closed
            pathid = 4;
          }
          $location.path('/success/' + pathid);
        } else {
          alert(data['reason']);
        }
      });
    }
  };

  $scope.sendResponse = function(formData, isValid) {
    const source_valid = typeof formData.source != 'undefined';

    $scope.response_error_message = '';
    if (
      formData['response_permission'] == 0 ||
      formData['response_permission'] == 4 ||
      (isValid && source_valid)
    ) {
      $scope.submitDisabled = true;
      // if user has chosen "I have nothing to contribute" button,
      // formData comes in as object response_permissions: 0
      // if user chooses "Active Search", object is response_permissions: 4
      formData['event_id'] = $routeParams.id;
      if ($routeParams.id) {
        var eid = $routeParams.id;
      }
      formData['files'] = $scope.ufiles;
      http({
        url: urlBase + 'scripts/sendresponse2.php',
        method: 'POST',
        data: formData,
      }).then(function successCallback(res) {
        const data = res.data;
        if (data['status'] === 'success') {
          $location.path('/success/2/' + eid);
        } else {
          alert('response failed!');
        }
        $scope.submitDisabled = false;
      });
    } else {
      if (isValid && !source_valid) {
        $scope.response_error_message = 'missing verification sources';
      }
    }
  };

  $scope.deleteEvent = function(eid) {
    if (confirm('Are you sure you want to delete this event?')) {
      const data = {eid: eid};
      http({
        url: urlBase + 'scripts/deleteEvent2.php',
        method: 'POST',
        data: data,
      }).then(function successCallback(res) {
        const data = res.data;
        if (data['status'] === 'success') {
          $location.path('/success/7');
        } else {
          alert(data['reason']);
        }
      });
    }
  };

  // Show summary modal
  $scope.showModal = false;
  $scope.modalTitle = '';
  $scope.modalBody = '';
  $scope.showSummary = function(
    summary,
    more_info,
    event_title,
    event_source,
    event_source_details,
    event_outcome,
    event_action_date,
  ) {
    let source = '';
    if (event_source == 'MR') {
      source = 'Media Report';
    } else if (event_source == 'OR') {
      source = 'Official Report';
    } else if (event_source == 'OC') {
      source = 'Other communication';
    }

    let outcome = 'Pending';
    if (event_outcome == 'VP') {
      outcome = 'Verified (positive)';
    } else if (event_outcome == 'VN') {
      outcome = 'Verified (negative)';
    } else if (event_outcome == 'UV') {
      outcome = 'Unverified';
    } else if (event_outcome == 'UP') {
      outcome = 'Updated (positive)';
    } else if (event_outcome == 'NU') {
      outcome = 'Updated (negative)';
    }

    const event_info =
      'Title: ' +
      event_title +
      '\r\n\r\n' +
      'Initial Source: ' +
      source +
      ':' +
      event_source_details +
      '\r\n\r\n' +
      'RFI Outcome: ' +
      outcome +
      '\r\n\r\n';

    $scope.modalTitle = 'Summary';
    $scope.modalBody = '';
    if (more_info) {
      $scope.modalBody =
        event_info +
        'RFI Closure Date: ' +
        event_action_date +
        '\r\n\r\n' +
        'PHE Description:\r\n' +
        summary +
        '\r\n\r\n' +
        'Additional Info:\r\n' +
        more_info;
    } else if (summary) {
      $scope.modalBody =
        event_info +
        'RFI Closure Date: ' +
        event_action_date +
        '\r\n\r\n' +
        'PHE Description:\r\n' +
        summary;
    }

    $scope.showModal = !$scope.showModal;
  };
};

EventsController2.$inject = [
  '$scope',
  '$window',
  '$rootScope',
  '$routeParams',
  '$cookieStore',
  '$location',
  'httpServiceInterceptor',
  'eventAPIservice2',
  'urlBase',
  'epicoreMode',
  'epicoreVersion',
  'Upload',
  '$timeout',
  'epicoreStartDate',
];

export default EventsController2;
