controllers.controller(
  "eventsController",
  function (
    $scope,
    $routeParams,
    $cookieStore,
    $location,
    $http,
    eventAPIservice,
    urlBase,
    epicoreMode
  ) {
    $scope.mobile = epicoreMode == "mobile" ? true : false;
    $scope.isRouteLoading = true;
    $scope.eventsList = [];
    $scope.userInfo = $cookieStore.get("epiUserInfo");
    $scope.id = $routeParams.id ? $routeParams.id : null;
    $scope.allFETPs = $routeParams.response_id ? false : true;
    // if we're on the closed requests page
    $scope.onOpen = $location.path().indexOf("/closed") > 0 ? false : true;
    $scope.anonymous_disabled = false;
    if (!$scope.formData) {
      $scope.formData = {};
    }
    $scope.validResponses = 0;

    eventAPIservice.getEvents($scope.id).then(function successCallback(res) {
      var response = res.data;
      $scope.isOrganization = $scope.userInfo.fetp_id > 0 ? false : true;
      // if RFI requester is the logged in user or of same org, they get different action items
      if (response.EventsList != null) {
        $scope.isAuthorizedToFollowup =
          $scope.userInfo.organization_id ==
          response.EventsList.org_requester_id
            ? true
            : false;
        $scope.changeStatusText =
          response.EventsList.estatus == "C" ? "Re open" : "Close";
        $scope.changeStatusType =
          response.EventsList.estatus == "C" ? "reopen" : "close";
        $scope.isAuthorizedFETP = false;
        $scope.isRequester =
          response.EventsList.requester_id == $scope.userInfo.uid
            ? true
            : false;
        if (
          response.EventsList.fetp_ids != null &&
          response.EventsList.fetp_ids.indexOf($scope.userInfo.fetp_id) != -1
        ) {
          $scope.isAuthorizedFETP = true;
        }
        if (response.EventsList.fetp_ids) {
          $scope.num_fetp = response.EventsList.fetp_ids.length;
        }

        $scope.eventsList = response.EventsList;
        $scope.filePreview = response.EventsList.filePreview
          ? response.EventsList.filePreview
          : "";
      }

      //$scope.closedEvents = response.closedEvents;

      // get response
      $scope.response_text = "";
      if ($routeParams.response_id) {
        var formData = {};
        formData["uid"] = $scope.userInfo.uid;
        formData["org_id"] = $scope.userInfo.organization_id;
        formData["fetp_id"] = $scope.userInfo.fetp_id;
        formData["response_id"] = $routeParams.response_id;
        $http({
          url: urlBase + "scripts/getresponse.php",
          method: "POST",
          data: formData,
        }).then(function successCallback(res) {
          var respdata = res.data;
          $scope.response_text = respdata["response"];
          $scope.responder_id = respdata["responder_id"];
          $scope.permission_id = respdata["response_permission_id"];
        });
      }

      // count unrated responses in closed events
      $scope.num_notrated_responses = 0;
      if ($scope.onOpen) {
        $scope.num_notrated_responses = response.numNotRatedResponses;
      } else if ($scope.eventsList) {
        for (var n in $scope.eventsList.yours) {
          $scope.num_notrated_responses += parseInt(
            $scope.eventsList.yours[n].num_notrated_responses
          );
        }
      }

      // count responses with content
      for (var h in $scope.eventsList.history) {
        if (
          $scope.eventsList.history[h].permission !== "0" &&
          $scope.eventsList.history[h].type == "Member Response" &&
          $scope.userInfo.uid
        ) {
          $scope.validResponses++;
        }
      }

      // check unclosed RFIs with no activity in the last two weeks
      Date.prototype.yyyymmdd = function () {
        var yyyy = this.getFullYear().toString();
        var mm = (this.getMonth() + 1).toString(); // getMonth() is zero-based
        var dd = this.getDate().toString();
        return (
          yyyy +
          "-" +
          (mm[1] ? mm : "0" + mm[0]) +
          "-" +
          (dd[1] ? dd : "0" + dd[0])
        ); // padding
      };
      var d = new Date();
      $scope.date = d.setDate(d.getDate() - 14); // now minus 14 days
      $scope.unclosed = 0;
      for (var n in $scope.eventsList.yours) {
        newdate = $scope.eventsList.yours[n].num_followups[0].iso_date;
        if (newdate < d.yyyymmdd()) {
          $scope.unclosed++;
        }
      }
      $scope.isRouteLoading = false;
    });

    $scope.sendFollowup = function (formData, isValid) {
      if (isValid) {
        $scope.submitDisabled = true;
        formData["uid"] = $scope.userInfo.uid;
        formData["event_id"] = $routeParams.id;
        if ($routeParams.id) {
          var eid = $routeParams.id;
        }
        if ($routeParams.response_id) {
          formData["response_id"] = $routeParams.response_id;
        }
        $http({
          url: urlBase + "scripts/sendfollowup.php",
          method: "POST",
          data: formData,
        }).then(function successCallback() {
          $scope.submitDisabled = false;
          $location.path("/success/3/" + eid);
        });
      }
    };

    $scope.changeRequestStatus = function (formData, thestatus, isValid) {
      // count responses assessed as useful,used in promed, or not useful when closing an RFI
      // only for responses with content
      var useful_rids = [];
      var usefulpromed_rids = [];
      var notuseful_rids = [];
      if (
        isValid &&
        (thestatus == "Close" || thestatus == "Update") &&
        $scope.validResponses > 0
      ) {
        for (var h in $scope.eventsList.history) {
          var h_rid = $scope.eventsList.history[h].response_id;
          var h_type = $scope.eventsList.history[h].type;
          var h_fetp_id = $scope.eventsList.history[h].fetp_id;
          var h_orgid = $scope.eventsList.history[h].organization_id;
          var h_useful = $scope.eventsList.history[h].useful;
          var h_perm = $scope.eventsList.history[h].permission;
          if (
            h_type == "Member Response" &&
            h_perm !== "0" &&
            ($scope.userInfo.uid || h_fetp_id == $scope.userInfo.fetp_id) &&
            h_orgid == $scope.userInfo.organization_id
          ) {
            if (h_useful === null) {
              alert("Please assess all member responses.");
              $scope.close_message = "Please assess all member responses.";
              return false;
            } else if (h_useful === "1") {
              useful_rids.push(h_rid); // save useful response_ids
            } else if (h_useful === "2") {
              usefulpromed_rids.push(h_rid); // save useful promed response_ids
            } else {
              notuseful_rids.push(h_rid); // save not useful response_ids
            }
          }
        }
      }
      if (isValid) {
        $scope.submitDisabled = true;
        formData["event_id"] = $routeParams.id;
        formData["uid"] = $scope.userInfo.uid;
        formData["thestatus"] = thestatus;
        formData["useful_rids"] = useful_rids.toString();
        formData["usefulpromed_rids"] = usefulpromed_rids.toString();
        formData["notuseful_rids"] = notuseful_rids.toString();
        $http({
          url: urlBase + "scripts/changestatus.php",
          method: "POST",
          data: formData,
        }).then(function successCallback(res) {
          var data = res.data;
          if (data["status"] === "success") {
            $scope.submitDisabled = false;
            var pathid = 4;
            if (thestatus == "Update") {
              pathid = 8;
            } else if (thestatus == "Reopen") {
              pathid = 5;
            } else {
              // closed
              pathid = 4;
            }
            $location.path("/success/" + pathid);
          } else {
            alert(data["reason"]);
          }
        });
      }
    };

    $scope.sendResponse = function (formData, isValid) {
      if (formData["response_permission"] == 0 || isValid) {
        $scope.submitDisabled = true;
        // if user has chosen "I have nothing to contribute" button,
        // formData comes in as object response_permissions: 0
        formData["event_id"] = $routeParams.id;
        formData["fetp_id"] = $scope.userInfo.fetp_id;
        if ($routeParams.id) {
          var eid = $routeParams.id;
        }
        $http({
          url: urlBase + "scripts/sendresponse.php",
          method: "POST",
          data: formData,
        }).then(function successCallback(res) {
          var data = res.data;
          if (data["status"] === "success") {
            $location.path("/success/2/" + eid);
          } else {
            alert("response failed!");
          }
          $scope.submitDisabled = false;
        });
      }
    };

    $scope.deleteEvent = function (eid) {
      if (confirm("Are you sure you want to delete this event?")) {
        data = { eid: eid, superuser: $scope.userInfo.superuser };
        $http({
          url: urlBase + "scripts/deleteEvent.php",
          method: "POST",
          data: data,
        }).then(function successCallback(res) {
          var data = res.data;
          if (data["status"] === "success") {
            $location.path("/success/7");
          } else {
            alert(data["reason"]);
          }
        });
      }
    };

    /* Request (RFI)
    this is the process to send an RFI. Store all values in window session
    and wipe session after added to db */
  }
);
