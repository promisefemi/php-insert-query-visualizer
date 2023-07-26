document.getElementById("editable").addEventListener("change", function (e) {
  let inputs = document.querySelectorAll("input, select, textarea");

  if (e.target.checked) {
    for (let i = 0; i < inputs.length; i++) {
      inputs[i].removeAttribute("disabled");
    }
  } else {
    for (let i = 0; i < inputs.length; i++) {
      inputs[i].setAttribute("disabled", "disabled");
    }
  }
});
