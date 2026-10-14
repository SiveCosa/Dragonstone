document.addEventListener("DOMContentLoaded", () => {
    const calcBtn = document.getElementById("calcBtn");
    if (!calcBtn) return;

    calcBtn.addEventListener("click", () => {
        const quantity = document.getElementById("calcQuantity").value;
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get("id");

        fetch("src/api/api_footprint.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `product_id=${productId}&quantity=${quantity}`,
        })
            .then((res) => res.json())
            .then((data) => {
                const resultDiv = document.getElementById("calcResult");
                if (data.error) {
                    resultDiv.innerHTML = `<div class='alert alert-danger'>${data.error}</div>`;
                    return;
                }
                resultDiv.innerHTML = `
          <div class='alert alert-success'>
            <strong>${data.message}</strong><br>
            Total Savings: <b>${data.total_savings.toFixed(2)} kg CO₂</b><br>
            EcoPoints Earned: <b>${(data.total_savings * 2).toFixed(0)}</b>
          </div>`;
            })
            .catch(() => {
                document.getElementById("calcResult").innerHTML =
                    "<div class='alert alert-danger'>Error calculating footprint.</div>";
            });
    });
});
