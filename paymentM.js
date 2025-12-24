document.addEventListener('DOMContentLoaded', function () {
    const codRadio = document.getElementById('cod');
    const cardRadio = document.getElementById('card');
    const cardForm = document.getElementById('cardform');

    function toggleCardForm() {
        if (cardRadio.checked) {
            cardForm.style.display = 'block';
        } else {
            cardForm.style.display = 'none';
        }
    }

    codRadio.addEventListener('change', toggleCardForm);
    cardRadio.addEventListener('change', toggleCardForm)
    toggleCardForm();
});