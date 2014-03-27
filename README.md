# sthree

A fieldtype for the Statamic CMS to upload files to Amazon Web Services (AWS) S3

It also provides automatic resizing of uploaded images to specified or user selected sizes. The resizing
is done using Intervention\Image which is bundled with Statamic and drives the awesome built-in
{{ transform }} tag.

## Requirements & Installation

sthree is built to run on Statamic v1.6 and above, it is untested on earlier versions. To install:

* Copy sthree/ into the /_addons/ directory
* Create a directory named 'sthree' in the /_config/addons/ directory
* Copy sthree.yaml into the /_config/addons/sthree/ directory
* Edit the sthree.yaml configuration file with your AWS details
* Add the field to your desired fieldset

## Addon configuration

```yaml
# These fields are the API access details for your AWS account
awsAccessKey: change-this
awsSecretKey: change-this
# This is the name of the end-point for the AWS API interface for your region
# From the list here: http://docs.aws.amazon.com/general/latest/gr/rande.html#s3_region
awsEndPoint: change-this                    # eg. s3-ap-southeast-2.amazonaws.com
# This is the name of the bucket to use in your S3 account
awsBucket: change-this
# This is the domain suffix for the bucket for web access for your region
# From the list here: http://docs.aws.amazon.com/AmazonS3/latest/dev/WebsiteEndpoints.html
awsDomainSuffix: change-this                # eg. s3-website-ap-southeast-2.amazonaws.com
```


## Fieldset configuration

The sthree fieldtype takes a variety of parameters.

To set it up, add *something like* the following to your fieldset:

```
fields:
  upload:
    type: sthree
    prefix: my/remote/dir/
    sizes:
      thumbnail:
        size1: Thumbnail;85x85x75
      small:
        small-landscape: Landscape;640x480x85
        small-portrait: Portrait;480x640x85
      big:
        big-landscape: Landscape;1024x768x75
        big-portrait: Portrait;768x1024x75
```

**Example Note**: the variables above - 'thumbnail', 'small', 'big', 'size1', 'small-landscape', 'small-portrait' etc - are all user generated - you can call them whatever you want.

* __prefix__ _(optional)_ specifies the prefix to add to the filename when uploading to AWS. This is usually in the form of a directory structure.
* __sizes__ _(optional)_ specifies the variety of sizes you want automatically created for images uploaded in this field, providing multiple options within a size group shows a radio button selection for each option
  * The first level array under sizes specifies the size groups. Only one selection can be made from each group and is accessible in the template through a variable with this name
  * Sizes under a size group must be in the following format:
    * __size_identifier__: __Size Name__;__X__ x __Y__ [x __Quality__]
    * size_identifier must be unique for that fieldset as it is used as the name for controls on the form
    * Quality (optional) must be an integer between 1 and 100
  * If only one size is listed under a size group, that option is hidden on the publish page and always used
  * If multiple sizes are listed under a size group, the list of sizes is displayed with radio buttons allowing the selection of a single size for each group.

## Template tags

Using our example above, the template tags to render these fields would be:

```
{{ upload }}
  <p>Original file: <img src="{{ original }}"></p>
  <p>Thumbnail: <img src="{{ thumbnail }}"></p>
  <p>Small: <img src="{{ small }}"></p>
  <p>Big: <img src="{{ big }}"></p>
{{ /upload }}
```
