<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:strip-space elements="*"/>
<xsl:output omit-xml-declaration="yes" indent="yes"/>
<xsl:template match="@*|node()">
    <xsl:copy>
        <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
</xsl:template>
<xsl:template match="//testsuite[string-length(@name)=0]|//testsuite[contains(@name,'::')]">
    <xsl:apply-templates/>
</xsl:template> 
<xsl:template match="error"/>
</xsl:stylesheet>
