function togglePassword() {
    var passwordField = document.getElementById("password");
    var checkBox = document.getElementById("showPassword");
    if (checkBox.checked) {
        passwordField.type = "text";
    } else {
        passwordField.type = "password";
    }
}

function registerWithGoogle() {
    alert("Google login is not implemented yet.");
}

function registerWithFacebook() {
    alert("Facebook login is not implemented yet.");
}
