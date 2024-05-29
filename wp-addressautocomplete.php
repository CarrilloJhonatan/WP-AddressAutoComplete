<?php
/*
Plugin Name: WP-AddressAutoComplete
Plugin URI: https://clickssmaster.com/
Description: A plugin to add a Google Maps API key to the settings.
Version: 1.0.2
Author: DeveloperAnonimous
Author URI: https://clickssmaster.com/
License: GPL 2+
License URI: https://clickssmaster.com/
*/

// Agregar el campo de configuración de la clave API en la página de ajustes generales
function myplugin_register_settings() {
    add_option('google_maps_api_key', '');
    register_setting('general', 'google_maps_api_key', 'esc_attr');

    add_settings_field(
        'google_maps_api_key',
        '<label for="google_maps_api_key">' . __('Google Maps API Key', 'google_maps_api_key') . '</label>',
        'myplugin_google_maps_api_key_html',
        'general'
    );
}
add_action('admin_init', 'myplugin_register_settings');

function myplugin_google_maps_api_key_html() {
    $value = get_option('google_maps_api_key', '');
    echo '<input type="text" id="google_maps_api_key" name="google_maps_api_key" value="' . $value . '" />';
}


// Función para cargar el script de Google Places en el encabezado
function load_google_places_script() {
    if (is_checkout()) {
        $api_key = get_option('google_maps_api_key');
        if ($api_key) {
            ?>
            <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr($api_key); ?>&libraries=places"></script>
            <?php
        }
           // Incluir directamente el archivo CSS personalizado desde la carpeta del plugin
        echo '<link rel="stylesheet" href="' . plugin_dir_url(__FILE__) . 'styleaddressautocomplete.css">';
    }
}
add_action('wp_head', 'load_google_places_script');

// Función para ajustar la posición del contenedor de autocompletado
function adjust_autocomplete_position() {
    if (is_checkout()) {
        ?>
        <script>
            function adjustAutocompletePosition() {
                var input = document.getElementById('billing_address_1');
                var pacContainer = document.querySelector('.woocommerce-checkout .pac-container2');
                if (pacContainer && input) {
                    var inputRect = input.getBoundingClientRect();
                    var parentRect = input.parentElement.getBoundingClientRect();

                    pacContainer.style.removeProperty('width');
                    pacContainer.style.removeProperty('left');

                    pacContainer.style.setProperty('width', `${inputRect.width}px`, 'important');
                    pacContainer.style.setProperty('left', `${(inputRect.left - parentRect.left)}px`, 'important');
                    pacContainer.style.setProperty('top', `${(inputRect.top - parentRect.top + input.offsetHeight)}px`, 'important');
                    pacContainer.style.setProperty('display', 'block', 'important');
                    pacContainer.style.setProperty('position', 'absolute', 'important');
                }
            }

            function initAutocomplete() {
                if (typeof google !== 'undefined' && google.maps && google.maps.places) {
                    var input = document.getElementById('billing_address_1');
                    var options = {
                        types: ['address'],
                        componentRestrictions: { country: 'co' }
                    };
                    var autocomplete = new google.maps.places.Autocomplete(input, options);

                    var observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.classList && node.classList.contains('pac-container')) {
                                    node.classList.remove('pac-container');
                                    node.classList.add('pac-container2');
                                }
                            });
                        });
                    });

                    observer.observe(document.body, { childList: true, subtree: true });

                    input.addEventListener('focus', function() {
                        setTimeout(adjustAutocompletePosition, 10);
                    });

                    autocomplete.addListener('place_changed', function() {
                        var place = autocomplete.getPlace();
                        var addressComponents = place.address_components;
                        var isAtlantico = false;

                        addressComponents.forEach(function(component) {
                            var types = component.types;
                            if (types.indexOf('administrative_area_level_1') > -1 && component.long_name === 'Atlántico') {
                                isAtlantico = true;
                            }
                        });

                        if (!isAtlantico) {
                            alert('Por favor seleccione una dirección en Atlántico, Colombia.');
                            input.value = '';
                            return;
                        }

                        addressComponents.forEach(function(component) {
                            var types = component.types;
                            if (types.indexOf('locality') > -1) {
                                document.getElementById('billing_city').value = component.long_name;
                            }
                            if (types.indexOf('administrative_area_level_1') > -1) {
                                document.getElementById('billing_state').value = component.short_name;
                            }
                            if (types.indexOf('postal_code') > -1) {
                                document.getElementById('billing_postcode').value = component.long_name;
                            }
                        });
                    });
                }
            }

            document.addEventListener('DOMContentLoaded', initAutocomplete);
            window.addEventListener('resize', adjustAutocompletePosition);
        </script>
        <?php
    }
}
add_action('wp_head', 'adjust_autocomplete_position');