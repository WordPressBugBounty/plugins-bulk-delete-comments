let confirmMessage = 'Are you sure?';


function setConfirmMessage(message) {
    confirmMessage = message;
}


function confirmAction() {
    return confirm(confirmMessage);
}