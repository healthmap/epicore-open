import { cacheService } from '@/common/cacheService';
import { Modal } from '@/common/modal';

const { showModal } = Modal();

const {
  setMemberPortalInfoAll,
  setMemberPortalInfoPastYear,
  setMemberPortalInfoPastQuarter,
  getMemberPortalInfoAll,
  getMemberPortalInfoPastYear,
  getMemberPortalInfoPastQuarter,
  clearMemPortalCache
} = cacheService();

const ApprovalController = (
  $scope,
  $filter,
  $location,
  $route,
  $cookieStore,
  urlBase,
  epicoreV1StartDate,
  httpServiceInterceptor
) => {
  const http = httpServiceInterceptor.http;
  const currentLocation = $location.path();

  $scope.init = function () {
    $scope.sharedCacheMemInfo = getMemberPortalInfoPastQuarter();
    // only allow superusers for admin
    $scope.userInfo = $cookieStore.get('epiUserInfo');
    $scope.superuser =
      typeof $scope.userInfo != 'undefined' ? $scope.userInfo.superuser : false;
    // $scope.superuser = true;
    $scope.showpage = false;
    $scope.delteWIP = false;
    $scope.membersavailable = false;
    $scope.eventsavailable = false;
    $scope.num_applicants = 0;
    $scope.num_accepted = 0;
    $scope.num_approved = 0;
    $scope.num_inactive = 0;
    $scope.num_denied = 0;
    $scope.num_preapproved = 0;
    $scope.num_setpassword = 0;
    $scope.allapp = false;
    const timeValues = [];
    timeValues.push({ name: 'All', value: 'all' });
    timeValues.push({ name: 'Past Year', value: 'past-year' });
    timeValues.push({ name: 'Past Quarter', value: 'recent' });
    $scope.event_months = timeValues.reverse();
    $scope.selected_month = timeValues[0]; // default past-quarter
    $scope.urlBaseStr = urlBase;
    let end_date = '';
    const start_date = moment().subtract(3, 'months').format('YYYY-MM-DD'); // three month ago- default for Past-Quarter
    end_date = moment().format('YYYY-MM-DD'); // now
    $scope.selected_start_date = start_date;
    $scope.selected_end_date = end_date;
    $scope.selectedItems = [];
    $scope.IsAllCollapsed = false;
    $scope.activeHeaderItem = '';
    $scope.searchTermSubmitted = false;
    $scope.displayAcceptedDateColumn = false;
    $scope.displayApprovedDateColumn = false;
    $scope.displayCourseColumn = false;
    $scope.displayPasswordColumn = false;
    $scope.predicateForSort = 'apply_date_iso';
    $scope.displayApplicantNumber = false;
    $scope.displayMemberNumber = false;

    $scope.pwcheck = false;
    $scope.displayHeaderGreenBar = false;

    $scope.outputList = [];
  };
  $scope.init();

  if (
    Object.keys($scope.sharedCacheMemInfo).length == 0 ||
    !$scope.sharedCacheMemInfo
  ) {
    var data = {};
    data.startDate = $scope.selected_start_date;
    data.endDate = $scope.selected_end_date;

    // Default tab - Accepted
    http({
      url: urlBase + 'scripts/approval.php',
      method: 'POST',
      data: data,
    }).then(function successCallback(res) {
      const respdata = res.data;
      // Fetch data from db
      setMemberPortalInfoPastQuarter(respdata); // since default is past-quarter only
      const tableData = $scope.loadMemberInfo(currentLocation);
    });
  }

  $scope.loadMemberInfo = function (currentLocation) {
    // Scope vars reset
    $scope.showpage = false;
    $scope.membersavailable = false;
    $scope.eventsavailable = false;
    $scope.num_applicants = 0;
    $scope.num_accepted = 0;
    $scope.num_approved = 0;
    $scope.num_inactive = 0;
    $scope.num_denied = 0;
    $scope.num_preapproved = 0;
    $scope.num_setpassword = 0;
    $scope.allapp = false;
    $scope.activeHeaderItem = '';
    $scope.searchTermSubmitted = false;
    $scope.displayAcceptedDateColumn = false;
    $scope.displayApprovedDateColumn = false;
    $scope.displayCourseColumn = false;
    $scope.displayPasswordColumn = false;
    $scope.displayApplicantNumber = false;
    $scope.displayMemberNumber = false;

    // Fetch memInfo from cache if available
    const month = $scope.selected_month;
    let memInfoData = [];
    if (month.value == 'past-year') {
      memInfoData = angular.copy(
        getMemberPortalInfoPastYear(),
      );
    } else if (month.value == 'all') {
      memInfoData = angular.copy(getMemberPortalInfoAll());
    } else {
      memInfoData = angular.copy(
        getMemberPortalInfoPastQuarter(),
      );
    }

    const currentdate = new Date();
    const currentFullYear = currentdate.getFullYear();
    const inactive_applicants = [];
    const accepted_applicants = [];
    const preapproved_applicants = [];
    const denied_applicants = [];
    const total_members = [];
    const accepted_applicants_nopw = [];
    const preapproved_applicants_nopw = [];

    for (const n in memInfoData) {
      memInfoData[n]['member_id'] = parseInt(memInfoData[n]['member_id']); // use int so orberby works

      const appl_year = $filter('date')(
        new Date(memInfoData[n]['apply_date']),
        'yyyy',
      );
      const accepted_year = $filter('date')(
        new Date(memInfoData[n]['accept_date']),
        'yyyy',
      );
      const approve_year = $filter('date')(
        new Date(memInfoData[n]['maillist_id']),
        'yyyy',
      );

      if (appl_year == currentFullYear) {
        memInfoData[n]['apply_date'] = $filter('date')(
          new Date(memInfoData[n]['apply_date']),
          'MMM dd',
        );
      } else {
        memInfoData[n]['apply_date'] = $filter('date')(
          new Date(memInfoData[n]['apply_date']),
          'dd MMM, yyyy',
        );
      }

      if (accepted_year == currentFullYear) {
        memInfoData[n]['accept_date'] = $filter('date')(
          new Date(memInfoData[n]['accept_date']),
          'MMM dd',
        );
      } else {
        memInfoData[n]['accept_date'] = $filter('date')(
          new Date(memInfoData[n]['accept_date']),
          'dd MMM, yyyy',
        );
      }

      if (approve_year == currentFullYear) {
        memInfoData[n]['approve_date'] = $filter('date')(
          new Date(memInfoData[n]['approve_date']),
          'MMM dd',
        );
      } else {
        memInfoData[n]['approve_date'] = $filter('date')(
          new Date(memInfoData[n]['approve_date']),
          'dd MMM, yyyy',
        );
      }

      memInfoData[n]['apply_date'] = memInfoData[n]['apply_date'].replace(
        /-/g,
        '',
      );
      memInfoData[n]['accept_date'] = memInfoData[n]['accept_date'].replace(
        /-/g,
        '',
      );
      memInfoData[n]['approve_date'] = memInfoData[n]['approve_date'].replace(
        /-/g,
        '',
      );

      if (memInfoData[n]['status'] == 'Pending') {
        accepted_applicants.push(memInfoData[n]);
        if (memInfoData[n]['pword'] != 'Yes') {
          accepted_applicants_nopw.push(memInfoData[n]);
        }
        $scope.num_accepted++;
      }
      if (memInfoData[n]['status'] == 'Approved') {
        total_members.push(memInfoData[n]);
        $scope.num_approved++;
      }
      if (memInfoData[n]['status'] == 'Inactive') {
        inactive_applicants.push(memInfoData[n]);
        $scope.num_inactive++;
      }
      if (memInfoData[n]['status'] == 'Denied') {
        denied_applicants.push(memInfoData[n]);
        $scope.num_denied++;
      }
      if (memInfoData[n]['status'] == 'Pre-approved') {
        preapproved_applicants.push(memInfoData[n]);
        if (memInfoData[n]['pword'] != 'Yes') {
          preapproved_applicants_nopw.push(memInfoData[n]);
        }
        $scope.num_preapproved++;
      }
      if (memInfoData[n]['pword'] == 'Yes') {
        $scope.num_setpassword++;
      }
    }

    switch (currentLocation) {
      case '/approval/accepted': {
        $scope.activeHeaderItem = 'Accepted';
        $scope.outputList = accepted_applicants;
        $scope.nppassword_applicants = accepted_applicants_nopw;
        $scope.displayAcceptedDateColumn = true;
        $scope.displayPasswordColumn = true;
        $scope.displayMemberNumber = true;
        break;
      }
      case '/approval/pre_approved': {
        $scope.activeHeaderItem = 'Pre Approved';
        $scope.outputList = preapproved_applicants;
        $scope.nppassword_applicants = preapproved_applicants_nopw;
        $scope.displayAcceptedDateColumn = true;
        $scope.displayPasswordColumn = true;
        $scope.displayCourseColumn = true;

        $scope.displayMemberNumber = true;

        break;
      }
      case '/approval/members': {
        $scope.activeHeaderItem = 'Members';
        $scope.outputList = total_members;
        $scope.nppassword_applicants = [];
        $scope.displayAcceptedDateColumn = true;
        $scope.displayApprovedDateColumn = true;
        $scope.displayCourseColumn = true;

        $scope.displayMemberNumber = true;
        break;
      }
      case '/approval/denied': {
        $scope.activeHeaderItem = 'Denied';
        $scope.nppassword_applicants = [];
        $scope.outputList = denied_applicants;
        $scope.displayApplicantNumber = true;
        break;
      }
      default: {
        $scope.activeHeaderItem = 'New Applicants';
        $scope.outputList = inactive_applicants;
        $scope.nppassword_applicants = [];
        $scope.displayApplicantNumber = true;
        break;
      }
    }

    $scope.displayAllRows = true;
    $scope.allapp = false;
    $scope.inactive_applicants = inactive_applicants;
    $scope.applicants = $scope.outputList;
    $scope.searchResetList = $scope.outputList;
    $scope.all_applicants = memInfoData;
    $scope.inactive_applicants = inactive_applicants;
    $scope.num_applicants = $scope.applicants.length;
    $scope.showpage = true;

    if ($scope.outputList.length > 0) {
      $scope.toggleRowExpandCollapse = true;
    } else {
      $scope.toggleRowExpandCollapse = false;
    }

    return memInfoData;
  };

  $scope.clearSearch = function (clickEvent) {
    if (clickEvent.target.attributes[0].nodeValue == 'far fa-search fa-times') {
      $scope.query_input = '';
      $scope.searchMembers('reset');
    }
  };

  $scope.searchMembers = function (keyEvent) {
    // $scope.query = $scope.query_input;
    const month = $scope.selected_month;
    $scope.sharedCacheMemInfo = [];
    if (month && month.value == 'past-year') {
      // $scope.sharedCacheMemInfo = getMemberPortalInfoPastYear();
      $scope.sharedCacheMemInfo = angular.copy(
        getMemberPortalInfoPastYear(),
      );
    } else if (month && month.value == 'all') {
      // $scope.sharedCacheMemInfo = getMemberPortalInfoAll();
      $scope.sharedCacheMemInfo = angular.copy(
        getMemberPortalInfoAll(),
      );
    } else {
      // $scope.sharedCacheMemInfo = getMemberPortalInfoPastQuarter();
      $scope.sharedCacheMemInfo = angular.copy(
        getMemberPortalInfoPastQuarter(),
      );
    }

    if (keyEvent.keyCode == 13) {
      $scope.query = $scope.query_input;
      $scope.searchTermSubmitted = true;
      if ($scope.query != '') {
        $scope.applicants = $scope.sharedCacheMemInfo;
      } else {
        $scope.applicants = $scope.applicants;
      }
    } else if (keyEvent == 'reset') {
      $scope.query = '';
      $scope.searchTermSubmitted = true;
      $scope.applicants = $scope.searchResetList;
    }
  };

  $scope.passwordCheck = function () {
    $scope.pwcheck = !$scope.pwcheck;
    if ($scope.pwcheck == true) {
      $scope.applicants = $scope.nppassword_applicants;
    } else {
      $scope.applicants = $scope.outputList;
    }
  };

  $scope.setVisible = function (visible) {
    angular.forEach($scope.applicants, function (applicant) {
      applicant.visible = visible;
    });
    $scope.displayAllRows = !$scope.displayAllRows;
  };

  $scope.isChecked = function (applicant) {
    if (applicant.Selected == true) {
      $scope.selectedItems.push(applicant.maillist_id);
    } else {
      $scope.selectedItems.splice(
        $scope.selectedItems.indexOf(applicant.maillist_id),
        1,
      );
    }
    if ($scope.selectedItems.length > 0) {
      $scope.displayHeaderGreenBar = true;
    } else {
      $scope.displayHeaderGreenBar = false;
      $scope.IsAllChecked = false;
    }
  };

  $scope.CheckUncheckHeader = function (user) {
    // $scope.IsAllChecked = true;
    $scope.displayHeaderGreenBar = true;
    const applicantItems = $scope.applicants;

    for (let i = 0; i < applicantItems.length; i++) {
      $scope.selectedItems.push(applicantItems[i].maillist_id);
      if (!user.Selected) {
        $scope.IsAllChecked = false;
        $scope.displayHeaderGreenBar = false;
        break;
      } else {
        $scope.displayHeaderGreenBar = true;
      }
    }
  };
  // $scope.CheckUncheckHeader();

  $scope.CheckUncheckAll = function () {
    $scope.displayHeaderGreenBar = !$scope.displayHeaderGreenBar;
    const applicantItems = $scope.applicants;
    for (let i = 0; i < applicantItems.length; i++) {
      $scope.selectedItems.push(applicantItems[i].maillist_id);
      $scope.applicants[i].Selected = $scope.IsAllChecked;
    }
  };

  // get member data for selected month
  $scope.getApprovalMonth = function () {
    $scope.isRouteLoading = true;
    const month = $scope.selected_month;
    let memInfoData = [];
    let start_date = '';
    let end_date = '';
    let num_events = 'all';
    if (month.value == 'all') {
      start_date = moment(epicoreV1StartDate).format('YYYY-MM-DD');
      end_date = moment().format('YYYY-MM-DD'); // now
      memInfoData = angular.copy(getMemberPortalInfoAll());
    } else if (month.value == 'recent') {
      start_date = moment().subtract(3, 'months').format('YYYY-MM-DD'); // three month ago
      end_date = moment().format('YYYY-MM-DD'); // now
      num_events = 10;
      memInfoData = angular.copy(
        getMemberPortalInfoPastQuarter(),
      );
    } else if (month.value == 'past-year') {
      start_date = moment().subtract(12, 'months').format('YYYY-MM-DD'); // one year ago
      end_date = moment().format('YYYY-MM-DD'); // now
      num_events = 10;
      memInfoData = angular.copy(
        getMemberPortalInfoPastYear(),
      );
    }

    $scope.selected_start_date = start_date;
    $scope.selected_end_date = end_date;
    const currentLocation = $location.path();

    if (memInfoData && memInfoData.length > 0) {
      // cache already has the data. No need of new pull from db
      const tableDataInfo = $scope.loadMemberInfo(currentLocation);
      $scope.isRouteLoading = false;
    } else {
      const data = {};
      data.startDate = $scope.selected_start_date;
      data.endDate = $scope.selected_end_date;

      // Fetch data from db
      http({
        url: $scope.urlBaseStr + 'scripts/approval.php',
        method: 'POST',
        data: data,
      }).then(function successCallback(res) {
        const respdata = res.data;
        // Fresh DB pull - set cache appropriately
        if (month.value == 'all') {
          setMemberPortalInfoAll(respdata);
        } else if (month.value == 'recent') {
          setMemberPortalInfoPastQuarter(respdata);
        } else if (month.value == 'past-year') {
          setMemberPortalInfoPastYear(respdata);
        }
        const tableData = $scope.loadMemberInfo(currentLocation);
        $scope.isRouteLoading = false;
      });
    }
  };

  $scope.setLocationStatus = function (maillist_id, action) {
    data = { maillist_id: maillist_id, action: action };
    http({
      url: urlBase + 'scripts/setLocationStatus.php',
      method: 'POST',
      data: data,
    }).then(function successCallback(res) {
      const respdata = res.data;
      if (respdata['status'] === 'success') {
        for (const n in $scope.applicants) {
          if ($scope.applicants[n].maillist_id == maillist_id) {
            $scope.applicants[n].locations = action == 'enable' ? '1' : '0';
          }
        }
      } else {
        alert(respdata['message']);
      }
    });
  };

  $scope.selectMembers = function (status) {
    const r = confirm('Please wait a little while if you select OK');
    if (r == true) {
      if (status) {
        $scope.allapp = true;
        $scope.applicants = $scope.all_applicants;
      } else {
        $scope.allapp = false;
        $scope.applicants = $scope.inactive_applicants;
      }
    }
  };

  $scope.approveApplicantHeader = function (maillist_id, action) { };
  /*
       --------------- Added by Sam ---------------------
       isHeader param here, is used to differentiate the incoming elements.
       If the action is from the header or from the child elements in the table.
       If header, then we loop through the list of maillist_ids and update status
  */

  $scope.approveApplicant = function (isHeader, maillist_id, action) {
    if (confirm('Are you sure to continue with \'' + action + '\' action?')) {
      if (isHeader == true && $scope.selectedItems.length > 1) {
        for (i = 0; i < $scope.selectedItems.length - 1; i++) {
          data = { maillist_id: $scope.selectedItems[i], action: action };
          $scope.updateMemberStatus(data);
        }
      } else {
        if (maillist_id == '') {
          maillist_id = $scope.selectedItems[0];
        }
        data = { maillist_id: maillist_id, action: action };
        $scope.updateMemberStatus(data);
      }
    }
  };

  $scope.updateMemberStatus = function (incomingData) {
    http({
      url: urlBase + 'scripts/setMemberStatus.php',
      method: 'POST',
      data: incomingData,
    }).then(function successCallback(res) {
      const respdata = res.data;
      if (respdata['status'] === 'success') {
        for (const n in $scope.applicants) {
          if ($scope.applicants[n].maillist_id == incomingData.maillist_id) {
            $scope.applicants[n].status = respdata['member_status'];
          }
        }
      } else {
        alert(respdata['message']);
      }
    });
  };

  /*
      ----------------------- END -------------------------------
  */
  $scope.downloadMembers = function () {
    $scope.isRouteLoading = true;
    http({
      url: urlBase + 'scripts/downloadMembers.php',
      method: 'POST',
    }).then(function successCallback() {
      $scope.membersavailable = true;
      $scope.isRouteLoading = false;
    });
  };

  $scope.downloadEvents = function () {
    $scope.isRouteLoading = true;
    http({
      url: urlBase + 'scripts/downloadEventStats.php',
      method: 'POST',
    }).then(function successCallback() {
      $scope.eventsavailable = true;
      $scope.isRouteLoading = false;
    });
  };

  $scope.sendReminderEmailToSelectedApplicants = function (action) {
    const sendEmailsPromisses = [];

    for (let i = 0; i < $scope.selectedItems.length; i++) {
      data = { action: action, memberid: $scope.selectedItems[i] };
      sendEmailsPromisses.push(
        new Promise(function (resolve) {
          http({
            url: urlBase + 'scripts/sendreminder.php',
            method: 'POST',
            data: data,
          }).then(function successCallback(response) {
            if (response.length > 0) {
              resolve(true);
            }
          });
        }),
      );
    }

    Promise.all(sendEmailsPromisses).then(() => {
      let modalMessage = 'The message has been sent.';
      if ($scope.selectedItems.length > 1) {
        modalMessage = `The message has been sent to ${$scope.selectedItems.length} persons.`;
      }
      showModal({
        id: 'success_message',
        header: 'Success',
        message: modalMessage,
      });
    });
  };

  $scope.sendReminder = function (action) {
    if (confirm('Are you sure you want to send reminder emails?')) {
      data = { action: action };
      http({
        url: urlBase + 'scripts/sendreminder.php',
        method: 'POST',
        data: data,
      }).then(function successCallback(res) {
        const respdata = res.data;
        alert(respdata.length + ' emails sent.');
      });
    } else {
    }
  };

  $scope.editApplicant = function (uid, action) {
    $location.path('/application/' + uid + '/' + action + '/member');
  };


  $scope.deleteApplicantAction = function () {

    $scope.delteWIP = false;
    const applicantsDeletedPromises = [];
    for (let i = 0; i < $scope.selectedItems.length; i++) {
      $scope.delteWIP = true;
      data = { uid: $scope.selectedItems[i] };
      applicantsDeletedPromises.push(
        new Promise(function (resolve) {
          http({
            url: urlBase + 'scripts/deleteuser.php',
            method: 'POST',
            data: data,
          }).then(function successCallback(response) {

            const respdata = response.data;
            return resolve(response.data);
          });
        }),
      );
    }

    Promise.all(applicantsDeletedPromises)
      .then(function (results) {
        $scope.delteWIP = false;
        let countSuccess = 0;
        let countFail = 0;
        results.forEach(obj => {
          Object.entries(obj).forEach(([key, value]) => {
            if (key === 'status' && value === 'success')
              countSuccess++;
            else if (key === 'status' && value === 'failed')
              countFail++;
          });
        });

        let modalMessage = `Successfully deleted ${countSuccess} applicants. Failed to delete ${countFail} records`;

        showModal({
          id: 'success_message',
          header: 'Success',
          message: modalMessage,
        });
      })
      .then(function () {
        //'finally reload - page as member portal needs a re-fetch');
        clearMemPortalCache();
        $route.reload();
      });

  };


  $scope.deleteSelectedApplicants = function () {

    let selectedItemsLength = $scope.selectedItems ? $scope.selectedItems.length : 0;
    //calling a modal with action.
    if (selectedItemsLength && selectedItemsLength > 0) {
      showModal({
        id: 'error_message',
        header: 'Delete Applicant',
        message: `You are about to delete ${selectedItemsLength} applicant(s). Applicant IDs: [ ${$scope.selectedItems} ] will be deleted. This action cannot be undone.`,
        action: 'delete-confirm',
        details: ''
      });
    }
  };
};

ApprovalController.$inject = [
  '$scope',
  '$filter',
  '$location',
  '$route',
  '$cookieStore',
  'urlBase',
  'epicoreV1StartDate',
  'httpServiceInterceptor'
];

export default ApprovalController;
