<template>
    <section>
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="">Product Name</label>
                            <input
                                type="text"
                                v-model="product_name"
                                placeholder="Product Name"
                                class="form-control"
                            />
                            <p class="text-danger" v-if="errors.title">
                                {{ errors.title[0] }}
                            </p>
                        </div>
                        <div class="form-group">
                            <label for="">Product SKU</label>
                            <input
                                type="text"
                                v-model="product_sku"
                                placeholder="Product Name"
                                class="form-control"
                            />
                            <p class="text-danger" v-if="errors.sku">
                                {{ errors.sku[0] }}
                            </p>
                        </div>
                        <div class="form-group">
                            <label for="">Description</label>
                            <textarea
                                v-model="description"
                                id=""
                                cols="30"
                                rows="4"
                                class="form-control"
                            ></textarea>
                            <p class="text-danger" v-if="errors.description">
                                {{ errors.description[0] }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div
                        class="card-header py-3 d-flex flex-row align-items-center justify-content-between"
                    >
                        <h6 class="m-0 font-weight-bold text-primary">Media</h6>
                    </div>
                    <div class="card-body border">
                        <vue-dropzone
                            ref="myVueDropzone"
                            id="dropzone"
                            :options="dropzoneOptions"
                            @vdropzone-file-added="addfile"
                        ></vue-dropzone>
                        <p class="text-danger" v-if="errors.product_image">
                            {{ errors.product_image[0] }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div
                        class="card-header py-3 d-flex flex-row align-items-center justify-content-between"
                    >
                        <h6 class="m-0 font-weight-bold text-primary">
                            Variants
                        </h6>
                    </div>
                    <div class="card-body">
                        <div
                            class="row"
                            v-for="(item, index) in product_variant"
                        >
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="">Option</label>
                                    <select
                                        v-model="item.option"
                                        class="form-control"
                                    >
                                        <option
                                            v-for="variant in variants"
                                            :value="variant.id"
                                        >
                                            {{ variant.title }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label
                                        v-if="product_variant.length != 1"
                                        @click="
                                            product_variant.splice(index, 1),
                                                checkVariant
                                        "
                                        class="float-right text-primary"
                                        style="cursor: pointer;"
                                        >Remove</label
                                    >
                                    <label v-else for="">.</label>
                                    <input-tag
                                        v-model="item.tags"
                                        @input="checkVariant"
                                        class="form-control"
                                    ></input-tag>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="card-footer"
                        v-if="
                            product_variant.length < variants.length &&
                                product_variant.length < 3
                        "
                    >
                        <button @click="newVariant" class="btn btn-primary">
                            Add another option
                        </button>
                    </div>

                    <div class="card-header text-uppercase">Preview</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <td>Variant</td>
                                        <td>Price</td>
                                        <td>Stock</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="variant_price in product_variant_prices"
                                    >
                                        <td>{{ variant_price.title }}</td>
                                        <td>
                                            <input
                                                type="text"
                                                class="form-control"
                                                v-model="variant_price.price"
                                            />
                                        </td>
                                        <td>
                                            <input
                                                type="text"
                                                class="form-control"
                                                v-model="variant_price.stock"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button
            @click="saveProduct"
            type="submit"
            class="btn btn-lg btn-primary"
        >
            {{ this.mode == "edit" ? "Update" : "Save" }}
        </button>
        <button
            type="button"
            class="btn btn-secondary btn-lg"
            @click="redirectToProduct"
        >
            Cancel
        </button>
    </section>
</template>

<script>
import vue2Dropzone from "vue2-dropzone";
import "vue2-dropzone/dist/vue2Dropzone.min.css";
import InputTag from "vue-input-tag";

export default {
    components: {
        vueDropzone: vue2Dropzone,
        InputTag
    },
    props: {
        variants: {
            type: Array,
            required: true
        },
        mode: {
            type: String, // specify the type of the prop
            default: "create" // set a default value
        },
        product: {
            type: Object
        }
    },
    data() {
        return {
            product_name: this.product?.title ?? "",
            product_sku: this.product?.sku ?? "",
            description: this.product?.description ?? "",
            images: [],
            product_variant: [
                {
                    option: this.variants[0].id,
                    tags: []
                }
            ],
            product_variant_prices: [],
            dropzoneOptions: {
                url: "https://httpbin.org/post",
                thumbnailWidth: 150,
                maxFilesize: 0.5,
                headers: { "My-Awesome-Header": "header value" }
            },
            errors: {},
            resource: {}
        };
    },
    methods: {
        addfile: async function(file) {
            // this.images = this.$refs.myVueDropzone.getAcceptedFiles();
            this.images.push(file);
            console.log(this.images);
        },
        // it will push a new object into product variant
        newVariant() {
            let all_variants = this.variants.map(el => el.id);
            let selected_variants = this.product_variant.map(el => el.option);
            let available_variants = all_variants.filter(
                entry1 => !selected_variants.some(entry2 => entry1 == entry2)
            );
            // console.log(available_variants)

            this.product_variant.push({
                option: available_variants[0],
                tags: []
            });
        },

        // check the variant and render all the combination
        checkVariant() {
            let tags = [];
            this.product_variant_prices = [];
            this.product_variant.filter(item => {
                tags.push(item.tags);
            });

            this.getCombn(tags).forEach(item => {
                this.product_variant_prices.push({
                    title: item,
                    price: 0,
                    stock: 0
                });
            });
        },

        // combination algorithm
        getCombn(arr, pre) {
            pre = pre || "";
            if (!arr.length) {
                return pre;
            }
            let self = this;
            let ans = arr[0].reduce(function(ans, value) {
                return ans.concat(
                    self.getCombn(arr.slice(1), pre + value + "/")
                );
            }, []);
            return ans;
        },

        // store product into database
        saveProduct() {
            let product = new FormData();
            product.append("title", this.product_name);
            product.append("sku", this.product_sku);
            product.append("description", this.description);
            for (let x = 0; x < this.images.length; x++) {
                product.append("product_image[]", this.images[x]);
            }
            for (let x = 0; x < this.product_variant.length; x++) {
                product.append(
                    "product_variant[]",
                    JSON.stringify(this.product_variant[x])
                );
            }
            for (let x = 0; x < this.product_variant_prices.length; x++) {
                product.append(
                    "product_variant_prices[]",
                    JSON.stringify(this.product_variant_prices[x])
                );
            }

            if (this.mode === "create") {
                axios
                    .post("/product", product)
                    .then(response => {
                        console.log(response.data);
                        if (
                            response.data.message ==
                            "Product added successfully"
                        ) {
                            window.location.href = "/product";
                        }
                    })
                    .catch(error => {
                        this.errors = error.response.data.errors;
                    });
            } else if (this.mode === "edit") {
                // Send a PUT request to update an existing resource
                axios
                    .post("/update-product/" + this.product?.id, product)
                    .then(response => {
                        console.log(response.data);
                        if (
                            response.data.message ==
                            "Product updated successfully"
                        ) {
                            window.location.href = "/product";
                        }
                    })
                    .catch(error => {
                        console.log(error);
                        this.errors = error.response.data.errors;
                    });
            }
        },

        // check the variant and render all the combination
        checkVariant() {
            let tags = [];
            this.product_variant_prices = [];
            this.product_variant.filter(item => {
                tags.push(item.tags);
            });

            this.getCombn(tags).forEach(item => {
                this.product_variant_prices.push({
                    title: item,
                    price: 0,
                    stock: 0
                });
            });
            this.loadPrices();
        },
        loadProductVariant() {
            let update;
            this.product.product_variants.forEach((item, index) => {
                this.product_variant &&
                    this.product_variant.forEach((item2, index2) => {
                        if (item2.option == item.variant_id) {
                            item2.tags.push(item.variant);
                            update = true;
                            return;
                        }
                    });
                if (!update) {
                    this.product_variant.push({
                        option: item.variant_id,
                        tags: [item.variant]
                    });
                }
                // console.log(this.product_variant[index])
            });
            console.log(this.product_variant);
        },
        loadPrices() {
            this.product.prices.forEach((item, index) => {
                if (this.product_variant_prices[index]) {
                    this.product_variant_prices[index].price = item.price;
                    this.product_variant_prices[index].stock = item.stock;
                }
            });
        },
        redirectToProduct() {
            window.location.href = "/product";
        }
    },
    mounted() {
        console.log("Component mounted.");
        console.log(this.product);
        if (this.mode == "edit") {
            this.loadProductVariant();
            this.checkVariant();
            this.loadPrices();
        }
    }
};
</script>
