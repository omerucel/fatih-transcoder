function checkboxChangePassword(checkbox)
{
    if ($(checkbox).is(':checked'))
    {
        $(checkbox).parent().parent().next().show();
    }else{
        $(checkbox).parent().parent().next().hide();
    }
}

function openFileUploadModal()
{

}