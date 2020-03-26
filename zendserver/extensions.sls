# This state enables and disables ZendServer PHP extensions as configured
# in the Pillar. If the bootstrapping of ZendServer wasn't done using
# a formula that stores the API key for admin as a Grain, this state halts.

# Beware: this state will issue a restart every time zs-manage has processed
# all extension-on and extension-off calls. (so at most once every highstate)

# Check if the server has been bootstrapped and the key was saved
{% if salt['grains.get']('zendserver:api:enabled', False) == True %}

# Get the key
{% set zend_api_key = salt['grains.get']('zendserver:api:key') %}

# Get JQD settings
{% set jqd_http_retries = salt['pillar.get']('zendserver:jqd:http_retry_count', '10') %}

# Get PHP version settings
{% set php_version = salt['pillar.get']('zendserver:version:php', '5.5') %}

# Enable extensions if set
zendserver.enable_extensions:
 cmd.run:
    - name: true; {% if 'enable_extensions' in salt['pillar.get']('zendserver', {}) -%}
{% for extension_on in salt['pillar.get']('zendserver:enable_extensions') -%}
/usr/local/zend/bin/zs-manage extension-on -e {{ extension_on }} -N admin -K {{ zend_api_key }}; {% endfor -%}
{% set must_restart_zend = True %}
{% endif %}

# Disable extensions if set
zendserver.disable_extensions:
 cmd.run:
    - name: true; {% if 'disable_extensions' in salt['pillar.get']('zendserver', {}) -%}
{% for extension_off in salt['pillar.get']('zendserver:disable_extensions') -%}
/usr/local/zend/bin/zs-manage extension-off -e {{ extension_off }} -N admin -K {{ zend_api_key }}; {% endfor -%}
{% set must_restart_zend = True %}
{% endif %}

zendserver.changePhpVersion:
 cmd.run:
   - name: /etc/zendserver/changePhpVersion.php admin {{ zend_api_key }} {{ php_version }}

zendserver.changeJobQueueRetryCount:
  cmd.run:
    - name: /etc/zendserver/changeJobQueueRetryCount.php admin {{ zend_api_key }} {{ jqd_http_retries }}

# If and extension was changed, we restart as a precaution. Most extensions requre a restart to be activated.
{% if must_restart_zend %}
zendserver.restart_because_extension_mutation:
 cmd.run:
    - name: /usr/local/zend/bin/zs-manage restart -N admin -K {{ zend_api_key }}
{% endif %}

{% endif %}
