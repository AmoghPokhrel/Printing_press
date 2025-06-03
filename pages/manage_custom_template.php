<script>
    function getPriceRangeLimits(priceRange) {
        switch (priceRange) {
            case 'Under Rs 50':
                return { min: 0, max: 49 };
            case 'Rs 50 - Rs 100':
                return { min: 50, max: 100 };
            case 'Rs 100 - Rs 200':
                return { min: 100, max: 200 };
            case 'Rs 200 - Rs 500':
                return { min: 200, max: 500 };
            case 'Above Rs 500':
                return { min: 500, max: 999999 }; // Setting a high max value
            default:
                return { min: 0, max: 0 };
        }
    }

    function validatePriceInput(input, priceRange) {
        const limits = getPriceRangeLimits(priceRange);
        const value = parseFloat(input.value);
        const priceError = document.getElementById('priceError');
        const submitButton = document.getElementById('updateStatusBtn');

        // Set min and max attributes
        input.setAttribute('min', limits.min);
        input.setAttribute('max', limits.max);

        if (isNaN(value) || value === '') {
            priceError.textContent = 'Please enter a valid price';
            priceError.style.display = 'block';
            submitButton.disabled = true;
            return false;
        }

        // Force the value to stay within limits
        if (value < limits.min) {
            input.value = limits.min;
            priceError.textContent = `Minimum price for ${priceRange} is Rs ${limits.min}`;
            priceError.style.display = 'block';
            submitButton.disabled = true;
            return false;
        }

        if (value > limits.max) {
            input.value = limits.max;
            priceError.textContent = `Maximum price for ${priceRange} is Rs ${limits.max}`;
            priceError.style.display = 'block';
            submitButton.disabled = true;
            return false;
        }

        priceError.style.display = 'none';
        submitButton.disabled = false;
        return true;
    }

    // Modify the existing toggleFinalDesignUpload function
    function toggleFinalDesignUpload(selectElement) {
        const uploadField = document.getElementById('finalDesignUpload');
        const priceInputGroup = document.getElementById('priceInputGroup');
        const isRevision = document.getElementById('is_revision').value === 'true';

        uploadField.style.display = selectElement.value === 'Completed' ? 'block' : 'none';

        if (selectElement.value === 'Completed' && !isRevision) {
            priceInputGroup.style.display = 'block';
            const priceRange = document.getElementById('price_range').value;
            const limits = getPriceRangeLimits(priceRange);
            const priceInput = document.getElementById('design_price');

            // Set initial min and max attributes
            priceInput.setAttribute('min', limits.min);
            priceInput.setAttribute('max', limits.max);

            // Add input and change event listeners for real-time validation
            priceInput.addEventListener('input', function () {
                validatePriceInput(this, priceRange);
            });

            priceInput.addEventListener('change', function () {
                validatePriceInput(this, priceRange);
            });

            // Add blur event to force value within limits when user leaves the field
            priceInput.addEventListener('blur', function () {
                const value = parseFloat(this.value);
                if (value < limits.min) {
                    this.value = limits.min;
                } else if (value > limits.max) {
                    this.value = limits.max;
                }
                validatePriceInput(this, priceRange);
            });

            document.getElementById('priceRangeInfo').textContent =
                `Price must be between Rs ${limits.min} and Rs ${limits.max} for ${priceRange}`;
        } else {
            priceInputGroup.style.display = 'none';
        }
    }

    // Add form submission validation
    document.getElementById('statusForm').addEventListener('submit', function (e) {
        const status = document.getElementById('new_status').value;
        const isRevision = document.getElementById('is_revision').value === 'true';

        if (status === 'Completed' && !isRevision) {
            const priceRange = document.getElementById('price_range').value;
            const priceInput = document.getElementById('design_price');

            if (!validatePriceInput(priceInput, priceRange)) {
                e.preventDefault();
                return;
            }

            const finalDesign = document.getElementById('final_design');
            if (!finalDesign.files || finalDesign.files.length === 0) {
                e.preventDefault();
                alert('Please upload a final design file.');
                return;
            }
        }
    });
</script>