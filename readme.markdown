# Unique link field #

This extension allows you to create a link to an unique URL, for example:
when you want to send someone an e-mail with a link which is only valid for 24 hours.

## How does it work? ##

Simply add the field to your section. You can set the following parameters
- The link, with 2 parameters: [URL] and [CODE]. So something like _[URL]/signup/[CODE]/_ would translate to **http://www.domain.com/signup/1421d689f8a0c388efaa59d2c16390f048677c44/**.
- How many hours the link will be valid (defaults to 24 hours).
- Check whether the entry should be deleted as soon as the link is no longer valid.

## So how can I use this in my site then? ##

Simply create a page with a parameter called `code` (for example) and add a datasource
with the unique field link to it.

Next, use some XSL logic. For example, something like this:

    <xsl:choose>
        <xsl:when test="$code = my-datasource/entry/link/@code">

            <!-- The code is valid! Show some content, form, etc... -->

        </xsl:when>
        <xsl:otherwise>

            <!-- The code is not valid! Show some error message or something -->

        </xsl:otherwise>
    </xsl:choose>
