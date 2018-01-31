<?php

// <INJECTION>

class Z
{
    use A {
        A// <MARKER> as B;
    }
}
