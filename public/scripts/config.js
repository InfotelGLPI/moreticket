    function hide_show_waiting(val) {
    var display = (val == 0) ? 'none' : '';
    console.log("🧩 hide_show_waiting > display =", display);

    const elem = document.getElementById('waiting_settings');
    if (elem) {
    elem.style.display = display;
} else {
    console.warn("⚠️ Element #waiting_settings non trouvé !");
}
}

    function hide_show_solution(val) {
    var display = (val == 0) ? 'none' : '';
    // const solutionElem = document.getElementById('solution_settings');
    // if (solutionElem) solutionElem.style.display = display;
    const mandatoryElem = document.getElementById('mandatory_solution');
    if (mandatoryElem) mandatoryElem.style.display = display;
}

    function hide_show_urgency(val) {
    var display = (val == 0) ? 'none' : '';
    const urgencyElem = document.getElementById('urgency_settings');
    if (urgencyElem) urgencyElem.style.display = display;
}

    document.addEventListener('DOMContentLoaded', function() {

    const useWaitingCheckbox = document.querySelector("input[type='checkbox'][name='use_waiting']");
    const useSolutionCheckbox = document.querySelector("input[type='checkbox'][name='use_solution']");
    const urgencyCheckbox = document.querySelector("input[type='checkbox'][name='urgency_justification']");

    // Fonction pour gérer l'affichage avec changement dynamique
    function updateWaiting() {
    hide_show_waiting(useWaitingCheckbox.checked ? 1 : 0);
}
    function updateSolution() {
    hide_show_solution(useSolutionCheckbox.checked ? 1 : 0);
}
    function updateUrgency() {
    hide_show_urgency(urgencyCheckbox.checked ? 1 : 0);
}

    if (useWaitingCheckbox) updateWaiting();
    if (useSolutionCheckbox) updateSolution();
    if (urgencyCheckbox) updateUrgency();

    if (useWaitingCheckbox) {
    useWaitingCheckbox.addEventListener('change', updateWaiting);
}
    if (useSolutionCheckbox) {
    useSolutionCheckbox.addEventListener('change', updateSolution);
}
    if (urgencyCheckbox) {
    urgencyCheckbox.addEventListener('change', updateUrgency);
}
});
