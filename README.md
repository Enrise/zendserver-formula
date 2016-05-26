# zendserver-formula

[![Travis branch](https://img.shields.io/travis/Enrise/zendserver-formula/master.svg?style=flat-square)](https://travis-ci.org/Enrise/zendserver-formula)

Formula for Saltstack which installs ZendServer with PHP packages of given version, and optionally configures it.
This was initially a [Saltstack-formulas repo](https://github.com/saltstack-formulas/zendserver-formula) but moved back to Enrise for better control & the ability of testing the formula using Travis.

## Compatibility

This formula currently only works on Debian-based systems (Debian, Ubuntu etc).

## Contributing

Pull requests for other OSes and bug fixes are more than welcome.

## Usage

Include `zendserver` in your project.

### Configuration

All the configuration for zendserver is done via pillar (pillar.example).
In case you already deployed your ZendServer installation but would like to enable extension management,
create a zendserver grain with your admin WebAPI key for zs-manage.
The format is as follows:

```yaml
  zend-server:
    api:
      enabled: True
      key: 8e454570fdb3601aaa2e63c95500643155573b4c095a991d4f51e21f24944baf
```

You could for example put that in a fresh file in `/etc/salt/minion.d/zendserver.conf`
