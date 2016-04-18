OroApiBundle
============

The goal of this bundle is to make a creation of different kinds of Data APIs as easy as possible.

The main idea of this bundle is to provide some default implementation which can be reused and easily changed for any entity if required.

To achieve this, this bundle is implemented based on two ORO components: [ChainProcessor](../../Component/ChainProcessor/) and [EntitySerializer](../../Component/EntitySerializer/). The ChainProcessor component is responsible to organize data processing flow. The EntitySerializer component provides the fast access to entities data.

**Notes**:
 - For now only GET and DELETE requests for REST and JSON.API are implemented.

Please see [documentation](./Resources/doc/index.md) for more details.
