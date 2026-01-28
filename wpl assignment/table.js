function showTable() {
  const tableBody = document.querySelector("#bioTable tbody");
  const biodataList = JSON.parse(localStorage.getItem('biodataList')) || [];

  if (biodataList.length === 0) {
    const row = document.createElement('tr');
    row.innerHTML = `<td colspan="5">No data found! Please fill the form first.</td>`;
    tableBody.appendChild(row);
    return;
  }

  biodataList.forEach((data, index) => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${index + 1}</td>
      <td>${data.name}</td>
      <td>${data.age}</td>
      <td>${data.email}</td>
      <td>${data.address}</td>
    `;
    tableBody.appendChild(row);
  });
}

function goBack() {
  window.location.href = 'index.html';
}

function clearData() {
  if (confirm("Are you sure you want to delete all records?")) {
    localStorage.removeItem('biodataList');
    location.reload();
  }
}

showTable();
