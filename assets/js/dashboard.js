async function refreshDashboard() {
  try {
    const response = await fetch('/SwinoTech/api/sensor-data.php', { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error('Sensor request failed');
    const reading = await response.json();
    document.getElementById('sensor-temp').textContent = `${reading.temperature} °C`;
    document.getElementById('sensor-humid').textContent = `${reading.humidity} %`;
    document.getElementById('sensor-temp-bar').style.width = `${Math.min(100, Math.max(0, (Number(reading.temperature) / 60) * 100))}%`;
    document.getElementById('sensor-humid-bar').style.width = `${Math.min(100, Math.max(0, Number(reading.humidity)))}%`;
    document.getElementById('sensor-time').textContent = reading.recorded_at || 'No readings yet';
    const environmentAlert = document.getElementById('heat-alert');
    const messages = [];
    if (Number(reading.temperature) > 32) messages.push('Heat-stress threshold exceeded.');
    if (reading.humidity_status === 'Low humidity') messages.push('Humidity is below the comfortable range.');
    if (reading.humidity_status === 'High humidity') messages.push('Humidity is above the comfortable range.');
    environmentAlert.textContent = messages.join(' ') + (messages.length ? ' Check the affected pen.' : '');
    environmentAlert.classList.toggle('d-none', messages.length === 0);
    environmentAlert.classList.toggle('alert-warning', reading.humidity_alert && Number(reading.temperature) <= 32);
    environmentAlert.classList.toggle('alert-danger', reading.alert && Number(reading.temperature) > 32);
    const status = document.getElementById('sensor-status');
    status.textContent = reading.alert ? (Number(reading.temperature) > 32 ? 'Heat stress' : reading.humidity_status) : 'Safe';
    status.className = `chip ${reading.alert ? 'chip-danger' : 'chip-success'}`;
  } catch (error) {
    document.getElementById('sensor-status').textContent = 'Unavailable';
    document.getElementById('sensor-status').className = 'chip chip-warning';
  }
}
refreshDashboard();
window.setInterval(refreshDashboard, 10000);
