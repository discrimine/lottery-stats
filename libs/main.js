document.addEventListener('DOMContentLoaded', () => {
  const preloader = document.getElementById('preloader');

  // import from csv
  document.getElementById('import-from-csv').addEventListener('change', function(e) {
    const bodyData = new FormData();
    
    bodyData.append('csv_file', this.files[0]);
    bodyData.append('action', 'csv_import');

    preloader.style.display = 'flex';
    fetch('api.php', {
      method: 'POST',
      body: bodyData,
    })
    .then(() => {
      preloader.style.display = 'none';
      location.reload();
    })
  });

  // remove the field
  document.querySelectorAll('span[action="remove-field"]').forEach(element => {
    element.addEventListener('click', function() {
      const dayId = this.getAttribute('day-id');
      const removeData = new FormData();
      removeData.append('day_id', dayId);
      removeData.append('action', 'delete_element');
      
      preloader.style.display = 'flex';
      fetch('api.php', {
        method: 'POST',
        body: removeData,
      })
      .then(() => {
        preloader.style.display = 'none';
        location.reload();
      })
    });
  });

  // click to add
  document.getElementById('add-day').addEventListener('click', () => {
    const lastNumberBody = new FormData();
    lastNumberBody.append('action', 'last_db_number');
    fetch('api.php', {
      method: 'POST',
      body: lastNumberBody
    })
    .then(lastNumber => lastNumber.json())
    .then(lastNumber => {
      const addDayContainer = document.querySelector('#add-day-container');
      addDayContainer.style.display = 'flex';
      addDayContainer.querySelector('#add-day-number').value = lastNumber + 1;
      // cancel
      addDayContainer.querySelector('#add-day-cancel').onclick = () => {
        addDayContainer.style.display = "none";
      };

      // save
      addDayContainer.querySelector('#add-day-save').onclick = () => {
        let valid = true;
        const newDayData = {
          number: addDayContainer.querySelector('#add-day-number').value,
          date: addDayContainer.querySelector('#add-day-date').value,
          firstNumber: addDayContainer.querySelector('#add-day-fn').value,
          secondNumber: addDayContainer.querySelector('#add-day-sn').value,
          thirdNumber: addDayContainer.querySelector('#add-day-tn').value,
        }
        
        // simple validation
        for (dataField in newDayData) {
          if (newDayData[dataField] === '') {
            valid = false;
          }
        }

        if (valid) {
          const newData = new FormData();
          newData.append('action', 'add_element');
          newData.append('number', newDayData.number);
          newData.append('date', newDayData.date);
          newData.append('fn', newDayData.firstNumber);
          newData.append('sn', newDayData.secondNumber);
          newData.append('tn', newDayData.thirdNumber);

          fetch('api.php', {
            method: 'POST',
            body: newData,
          })
          .then(() => {
            location.reload();
          });
        } else {
          alert('Заповніть всі поля!');
        }
      };
    });
  });

  preloader.style.display = 'none';
});