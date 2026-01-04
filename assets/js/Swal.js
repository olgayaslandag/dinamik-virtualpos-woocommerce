window. SwalWait = Swal.mixin({
    icon: 'info',
    title: 'Lütfen Bekleyin',
    text: 'Sunucudan bilgiler alınma işlemi biraz zaman alabilir. Beklediğiniz için teşekkürler.',
    showConfirmButton: false,
    allowOutsideClick: false
});

window. SwalConfirm = Swal.mixin({
    customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-danger'
    },
    icon: 'question',
    title: 'Emin misin?',
    text: 'Veri sistemden kalıcı olarak silinecek!',
    buttonsStyling: false,
    showConfirmButton: true,
    showCancelButton: true,
    confirmButtonText: 'Tamam',
    cancelButtonText: 'İptal',
});

window. SwalConfirmLogout = Swal.mixin({
    customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-danger'
    },
    icon: 'question',
    title: 'Emin misin?',
    text: 'Sistemden çıkış yapmak üzeresiniz!',
    buttonsStyling: false,
    showConfirmButton: true,
    showCancelButton: true,
    confirmButtonText: 'Tamam',
    cancelButtonText: 'İptal',
});


window. SwalNew = Swal.mixin({
    customClass: {
        confirmButton: 'btn btn-primary'
    },
    buttonsStyling: false,
    confirmButtonText: 'Tamam'
});

window. Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1500,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
})