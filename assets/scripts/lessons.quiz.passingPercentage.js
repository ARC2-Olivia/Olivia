window.addEventListener("load", () => {
    const passingPercentageWidget = document.getElementById("lesson_passingPercentage");
    const passingPercentageValue = document.getElementById("passing-percentage-value");
    passingPercentageValue.innerText = passingPercentageWidget.value + "%";

    passingPercentageWidget.addEventListener("input", (event) => {
        passingPercentageValue.innerText = event.target.value + "%";
    })
});