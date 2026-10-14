document.addEventListener("DOMContentLoaded", () => {
    const cartButtons = document.querySelectorAll(".add-to-cart");
    const cartCount = document.querySelector("#cart-count");
    const removeButtons = document.querySelectorAll(".remove-item");
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Add to cart
    cartButtons.forEach(btn => {
        btn.addEventListener("click", async () => {
            const productId = btn.dataset.id;
            try {
                const response = await fetch("/src/api/api_cart.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                        "X-CSRF-Token": csrfToken
                    },
                    body: new URLSearchParams({ action: "add", product_id: productId, quantity: "1", csrf_token: csrfToken }).toString()
                });
                const result = await response.json();
                if (result.status === 'ok') {
                    alert("Product added to cart!");
                    if (cartCount && result.cart && typeof result.cart.items !== 'undefined') {
                        cartCount.textContent = String(result.cart.items.length);
                    }
                } else {
                    alert(result.error || "Error adding product to cart.");
                }
            } catch (e) {
                alert("Network error while adding to cart.");
            }
        });
    });

    // Remove from cart
    removeButtons.forEach(btn => {
        btn.addEventListener("click", async () => {
            const cartItemId = btn.dataset.id; // should be cart_item_id
            try {
                const response = await fetch("/src/api/api_cart.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                        "X-CSRF-Token": csrfToken
                    },
                    body: new URLSearchParams({ action: "remove", cart_item_id: cartItemId, csrf_token: csrfToken }).toString()
                });
                const result = await response.json();
                if (result.status === 'ok') {
                    alert("Item removed from cart.");
                    location.reload();
                } else {
                    alert(result.error || "Error removing item.");
                }
            } catch (e) {
                alert("Network error while removing item.");
            }
        });
    });

    // Quantity change listener (if cart page has inputs)
    document.querySelectorAll(".cart-quantity").forEach(input => {
        input.addEventListener("change", async (e) => {
            const cartItemId = e.target.dataset.id; // should be cart_item_id
            const quantity = e.target.value;
            try {
                const response = await fetch("/src/api/api_cart.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                        "X-CSRF-Token": csrfToken
                    },
                    body: new URLSearchParams({ action: "update", cart_item_id: cartItemId, quantity: String(quantity), csrf_token: csrfToken }).toString()
                });
                const result = await response.json();
                if (result.status === 'ok') {
                    location.reload();
                } else {
                    alert(result.error || "Error updating quantity.");
                }
            } catch (e) {
                alert("Network error while updating item.");
            }
        });
    });
});

