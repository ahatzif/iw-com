import { module } from 'modujs';
import axios from 'axios';
import {info} from "autoprefixer";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'option': 'optionClick', 'thumb': 'changeImage' } };
    
        this.$price = this.el.querySelector("[data-product-color-variation='price']");
        this.$discountedPrice = this.el.querySelector("[data-product-color-variation='discounted-price']");
        this.placeholder = this.el.querySelector('[data-placeholder-product-single]');
        this.form = this.el.querySelector(".variations_form");
        this.selects = this.form ? this.form.querySelectorAll('select') : [];
        this.galleryImagesContainer = this.el.querySelector("[data-product-color-variation='gallery-images']");
        this.galleryDisplay = this.galleryImagesContainer?.dataset.productColorVariationGallery || 'thumbs';
        this.featuredImage = this.el.querySelector("[data-product-color-variation='product-featured-image'] img");

        if(this.form){
            this.variation = JSON.parse( this.form.getAttribute("data-product_variations") );
            this.variationAttributes = JSON.parse( this.form.getAttribute("data-variation-attributes"));
        } else {
            this.variation = [];
            this.variationAttributes = [];
        }


	        this.bindRadioEvents();
	        this.bindSelectEvents();
	        if (this.form) {
	            this.updateSelectedVariation();
	        }
	
	
	    }

    bindRadioEvents() {
        if (!this.form) {
            return;
        }

        const radioGroups = this.form.querySelectorAll('input[type="radio"]');
        radioGroups.forEach(radio => {
            radio.addEventListener('change', (e) => {
                const attributeName = e.target.name;
                const value = e.target.value;
                const select = this.form.querySelector(`select[name="${attributeName}"]`);
                if (select) {
                    select.value = value;
                }
                this.updateSelectedVariation();
            });
        });
    }

    bindSelectEvents() {
        this.selects.forEach(select => {
            select.addEventListener('change', (ev) => {
                this.updateSelectedVariation();
               // this.filterVariationChoicesNotForColor(ev);
            });
        });
    }


    optionClick(e) {

        // const attributeName = e.currentTarget.dataset.attr; // π.χ. attribute_pa_color
        const attributeName = e.currentTarget.closest('[data-attr]').dataset.attr;
        const value = e.currentTarget.dataset.value;
        this.activateOption(e.currentTarget);
        if (!this.form) {
            return;
        }

        const select = this.form.querySelector(`select[name="attribute_${attributeName}"]`);
        const selectedSelects = this.filterVariationChoicesForColor( attributeName, value);
        if (select) {
            select.value = value;
            selectedSelects.add(select);
        }
        this.updateSelectedVariation();
    }

    activateOption(option) {
        const group = option.closest('[data-attr]');
        if (!group) {
            return;
        }

        group.querySelectorAll('[data-product-color-variation="option"]').forEach(item => item.classList.remove('active'));
        option.classList.add('active');
    }


    updateSelectedVariation() {
        const selectedAttributes = {};

        // Μαζεύουμε τιμές από ΟΛΑ τα selects
        this.selects.forEach(select => {
            selectedAttributes[select.name] = select.value;
        });

        // Μαζεύουμε τιμές από όλα τα checked radios
        const radioGroups = this.form.querySelectorAll('input[type="radio"]:checked');

        radioGroups.forEach(radio => {
            selectedAttributes[radio.name] = radio.value;
        });

        this.form.querySelectorAll('[data-product-color-variation="product-variation"][data-attr]').forEach(group => {
            const activeOption = group.querySelector('[data-product-color-variation="option"].active');
            if (activeOption?.dataset.value) {
                selectedAttributes['attribute_' + group.dataset.attr] = activeOption.dataset.value;
            }
        });

        this.selectedAttributes = selectedAttributes;

        // Βρίσκουμε variation που ταιριάζει (αναλυτική εκδοχή)
        let selectedVariation = this.findMatchingVariation(selectedAttributes);
        this.syncVariationInput(selectedVariation);

        // this.updateTotalPrice(selectedVariation);
        this.updateDiscountedPrice(selectedVariation);
        this.createVariationGallery(selectedVariation)
        this.changeSKU(selectedVariation);
        this.togglePlaceholderImg(selectedVariation);        
    }

    findMatchingVariation(selectedAttributes) {
        let selectedVariation = null;

        this.variation.forEach((variation) => {
            let allMatch = true;

            Object.keys(variation.attributes).forEach(attrName => {
                const variationValue = variation.attributes[attrName] || "";
                const selectedValue = selectedAttributes[attrName] || "";
                if (variationValue !== "" && variationValue !== selectedValue) {
                    allMatch = false;
                }
            });

            if (allMatch && !selectedVariation) {
                selectedVariation = variation;
            }
        });

        return selectedVariation;
    }

    syncVariationInput(selectedVariation) {
        const variationIdInput = this.form?.querySelector('input[name="variation_id"]');
        if (!variationIdInput) {
            return;
        }

        variationIdInput.value = selectedVariation?.variation_id || '';
    }

    filterVariationChoicesForColor(attributeName, value){
        if (!this.form) {
            return new Set();
        }

        const targetAttribute = 'attribute_' + attributeName;
        const visibleValues = {};
        let firstExactAttributes = null;
        let firstFallbackAttributes = null;

        this.variationAttributes.forEach( obj => {
            const attrValue = obj[ targetAttribute] || '';
            const isExact = attrValue === value;
            const isFallback = attrValue === '';
            const isVisible = isExact || isFallback;

            if (isExact && ! firstExactAttributes) {
                firstExactAttributes = obj;
            } else if (isFallback && ! firstFallbackAttributes) {
                firstFallbackAttributes = obj;
            }

            if (! isVisible) {
                return;
            }

            Object.keys( obj ).forEach( attribute => {
                if( attribute !== targetAttribute && obj[attribute] ) {
                    visibleValues[attribute] ??= new Set();
                    visibleValues[attribute].add(obj[attribute]);
                }
            });

        });

        this.form.querySelectorAll('[data-variation-select]').forEach(label => {
            const [attribute, optionValue] = (label.dataset.variationSelect || '').split(':');
            if (!attribute || !visibleValues[attribute]) {
                return;
            }
            label.classList.toggle('hidden', ! visibleValues[attribute].has(optionValue));
        });

        const selectedSelects = new Set();
        const selectedAttributes = this.findPreferredAttributesForOption(targetAttribute, value) || firstExactAttributes || firstFallbackAttributes;
        if (selectedAttributes) {
            Object.keys(selectedAttributes).forEach(attribute => {
                if (attribute === targetAttribute || !selectedAttributes[attribute]) {
                    return;
                }

                const selectedSelect = this.form.querySelector(`select[name="${attribute}"]`);
                if (selectedSelect) {
                    selectedSelect.value = selectedAttributes[attribute];
                    selectedSelects.add(selectedSelect);
                }

                this.form.querySelectorAll(`input[type="radio"][name="${attribute}"]`).forEach(input => {
                    input.checked = input.value === selectedAttributes[attribute];
                });
            });
        }

        return selectedSelects;
    }

    findPreferredAttributesForOption(attributeName, value) {
        const matches = this.variation.filter(variation => {
            const variationValue = variation.attributes[attributeName] || '';
            return variationValue === value || variationValue === '';
        });

        const exactMatches = matches.filter(variation => variation.attributes[attributeName] === value);
        const fallbackMatches = matches.filter(variation => ! variation.attributes[attributeName]);
        const preferredVariation = exactMatches.find(variation => variation.is_in_stock && variation.is_purchasable)
            || exactMatches[0]
            || fallbackMatches.find(variation => variation.is_in_stock && variation.is_purchasable)
            || fallbackMatches[0];

        return preferredVariation?.attributes || null;
    }

    returnFilteredVariationAttributes(inputCheckedValue){
        const filtered = this.variationAttributes.filter(function(variation) {
            const values = Object.values(variation);
            return values.includes(inputCheckedValue);
        });

        return filtered;
    }

    getSiblingsDivs(inputCheckedParentDiv, functionName, filtered){
        const allSiblings = inputCheckedParentDiv.parentElement.querySelectorAll('[data-parent]');
        if (allSiblings.length > 0) {
            allSiblings.forEach(function(el) {
                if (el !== inputCheckedParentDiv) {
                    functionName(el, filtered);
                }
            });
        }
    }

    totggleLabelsAttrNotColor(el, filtered){
        let labels = el.querySelectorAll('label');

        labels.forEach(function(label) {
            label.style.opacity = '';
            let value = label.querySelector('input').getAttribute('value');
            const existsInFiltered = filtered.some(function(variation) {
                return Object.values(variation).includes(value);
            });

            setTimeout(() => {
                label.style.opacity = existsInFiltered ? '' : '0.3';          
            }, 500);


            
        });
    }

    totggleLabelsAttrColor(el, filtered){
        let options = el.querySelectorAll('[data-product-color-variation="option"]');

        options.forEach(function(option) {
            option.style.opacity = '';
            let value = option.getAttribute('data-value');

            console.log('value',value);

            const existsInFiltered = filtered.some(function(variation) {
                return Object.values(variation).includes(value);
            });
            setTimeout(() => {
                option.style.opacity = existsInFiltered ? '' : '0.3';
            }, 500);
        });
    }

    filterVariationChoicesNotForColor(ev) {
        let optionName = ev.target.getAttribute('name'); // attribute_pa_material
        if(optionName == 'attribute_pa_color'){ return; }
        console.log('filterVariationChoices');

        let inputChecked = this.form.querySelectorAll(`input[name="${optionName}"]:checked`);
        let inputCheckedValue;
        let inputCheckedParentDiv;

        if (inputChecked) {
            inputChecked = inputChecked[0];
            inputCheckedValue = inputChecked.getAttribute('value'); // keramiko
            inputCheckedParentDiv = inputChecked.closest('[data-parent]');
        }

        const filtered = this.returnFilteredVariationAttributes(inputCheckedValue);
        
        if (inputCheckedParentDiv) {
            this.getSiblingsDivs(inputCheckedParentDiv, this.totggleLabelsAttrColor, filtered);
        } 
    }


    togglePlaceholderImg(selectedVariation){
        this.placeholder?.classList.toggle('hidden', selectedVariation);
    }

    updateTotalPrice(selectedVariation) {
        // Αν βρήκαμε σωστό variation, ενημερώνουμε τιμή
        if (selectedVariation) {
            if(selectedVariation.price_html){
                this.$price.innerHTML = selectedVariation.price_html;
            }
        }
    }

    updateDiscountedPrice(selectedVariation) {
        if (selectedVariation) {
          // Raw αριθμητική τιμή
            const rawPrice = selectedVariation.display_price;
            const constdiscountedPrice = this.calcDiscountedPrice(rawPrice);
            if(this.$discountedPrice && constdiscountedPrice){
                this.$discountedPrice.innerHTML = constdiscountedPrice;
            }
        }
    }

    changeSKU(selectedVariation) {
        if (!selectedVariation) {
            return;
        }

        if(selectedVariation.sku){
            const sku = this.el.querySelector("[data-product-color-variation='sku']");
            if (sku) {
                sku.innerHTML = selectedVariation.sku;
            }
        }
    }

    calcDiscountedPrice(regularPrice, discountPercent = 10, decimal = true, htmlFormatted = true){
        let discounted = regularPrice - (regularPrice * discountPercent / 100);
        if(decimal){
            discounted = discounted.toFixed(2).replace('.', ',');
        }

        if(htmlFormatted){
            discounted = `<span class="woocommerce-Price-amount amount"><bdi>${discounted}&nbsp;<span class="woocommerce-Price-currencySymbol">€</span></bdi></span>`;
        }
        return discounted;
    }

    createVariationGallery(selectedVariation) {
        if (!selectedVariation) {
            if (this.galleryImagesContainer) {
                this.galleryImagesContainer.innerHTML = '';
            }
            return;
        }

        const mainImageId = selectedVariation.image?.image_id || '';
        const mainImageUrl = selectedVariation.image?.url || '';

        if (mainImageUrl && this.featuredImage) {
            this.featuredImage.src = mainImageUrl;
        }

        if (!this.galleryImagesContainer) {
            return;
        }

        this.galleryImagesContainer.innerHTML = '';

        const renderedImages = new Set();
        const addImage = (thumbUrl, imageUrl, imageId = '') => {
            if (!imageUrl) {
                return;
            }

            const imageKey = imageId || imageUrl;
            if (renderedImages.has(imageKey) || renderedImages.has(imageUrl)) {
                return;
            }

            renderedImages.add(imageKey);
            renderedImages.add(imageUrl);
            const isFirstImage = this.galleryImagesContainer.children.length === 0;
            this.galleryImagesContainer.insertAdjacentHTML('beforeend', this.createImage(thumbUrl, imageUrl, isFirstImage));
        };

        const galleryImages = selectedVariation.gallery || [];
        const isMainImage = (image) => {
            const imageId = image.ID || image.id || '';
            const imageUrl = image.url || image.sizes?.["iw-theme-product-photo"] || '';
            return (mainImageId && imageId && mainImageId === imageId) || (mainImageUrl && imageUrl && mainImageUrl === imageUrl);
        };
        const hasExtraGalleryImages = galleryImages.some(image => ! isMainImage(image));

        if (mainImageUrl && ! hasExtraGalleryImages) {
            addImage(selectedVariation.image.gallery_thumbnail_src, mainImageUrl, mainImageId);
        }

        galleryImages.forEach((image) => {
            if (isMainImage(image)) {
                return;
            }

            addImage(image.sizes.thumbnail, image.sizes["iw-theme-product-photo"], image.ID || image.id);
        });

        if (this.galleryDisplay === 'bullets') {
            this.galleryImagesContainer.classList.toggle('hidden', this.galleryImagesContainer.children.length <= 1);
        }
    }


    createImage(thumburl, image, active = false) {
        if (this.galleryDisplay === 'bullets') {
            return `
                <button type="button" class="size-10 rounded-full border border-dark/40 bg-white/80 transition duration-300 [&.active]:bg-dark ${active ? 'active' : ''}" data-product-color-variation="thumb" data-image="${image}"></button>
            `;
        }

        return `
            <div class="rounded ${active ? 'active' : ''}" data-product-color-variation="thumb">
                <img src="${thumburl}" class="cursor-pointer" data-image="${image}" />
            </div>
        `;
    }

    changeImage(e) {
        const clickedThumb = e.currentTarget;
        const clickedImage = clickedThumb.querySelector('img') || clickedThumb;
        const image = clickedThumb.dataset.image || clickedImage?.dataset.image;

        if (this.featuredImage && image) {
            this.featuredImage.src = image;
        }

        const gallery = clickedThumb.closest('[data-product-color-variation="gallery-images"]');
        if (gallery) {
            gallery.querySelectorAll('[data-product-color-variation="thumb"]').forEach(thumb => thumb.classList.remove('active'));
            clickedThumb.classList.add('active');
        }
    }




    
    

    
}
