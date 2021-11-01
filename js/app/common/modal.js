const Modal = () => {
  const showModal = ({ id, header, message, details, action }) => {

    const modalContent = `<div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">${header}</h4>
        </div>
        <div class="modal-body">
          <p>${message}</p>
          ${details ? `<h5>Details:</h5><p class="small modal-details">${details}</p>` : ''}
        </div>
        <div class="modal-footer">
          ${action ? '<button id="btnDeleteConfirm" type="button" class="btn btn-primary" data-dismiss="modal">Delete</button>' : ''}
          <button id="btnClose" type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
    <script>
      $(document).ready(function () {
        $('#btnDeleteConfirm').click(function () {
          angular.element(document.getElementById('deleteApplicantMember')).scope().deleteApplicantAction();
        });
      });
    </script>`;

    let modalInstance = $(`#${id}`);

    if (modalInstance.length === 0) {
      $('body').append(`<div id="${id}" class="modal fade"></div>`);

      modalInstance = $(`#${id}`);

    }

    modalInstance.html(modalContent);
    modalInstance.modal('show');

  };

  return {
    showModal
  };
};

export { Modal };