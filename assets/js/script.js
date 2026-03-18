document.addEventListener("DOMContentLoaded", function () {
    const alerts = document.querySelectorAll(".auto-dismiss");
    alerts.forEach(function (alertItem) {
        setTimeout(function () {
            alertItem.classList.add("fade");
        }, 2800);
        setTimeout(function () {
            alertItem.remove();
        }, 3400);
    });
});
